const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const compression = require('compression');
const mongoose = require('mongoose');
const swaggerUi = require('swagger-ui-express');
require('dotenv').config();

const logger = require('./Utils/logger');
const errorHandler = require('./middleware/errorHandler');
const { generalLimiter } = require('./middleware/rateLimiter');
const { connectRedis } = require('./config/redis');
const swaggerSpec = require('./config/swagger');

// Route imports
const authRoutes = require('./routes/auth');
const userRoutes = require('./routes/users');
const dataRoutes = require('./Routes/data');

// Initialize Express app
const app = express();

// Trust proxy (needed for rate limiting behind load balancers / Heroku / Nginx)
app.set('trust proxy', 1);

// Disable x-powered-by header
app.disable('x-powered-by');

// =========================================================================
// SECURITY MIDDLEWARE
// =========================================================================
app.use(helmet({
  contentSecurityPolicy: process.env.NODE_ENV === 'production' ? undefined : false,
  crossOriginEmbedderPolicy: false
}));

// CORS configuration
const corsOptions = {
  origin: (origin, callback) => {
    // Allow requests with no origin (mobile apps, curl, Postman)
    if (!origin) return callback(null, true);

    const allowedOrigins = process.env.ALLOWED_ORIGINS
      ? process.env.ALLOWED_ORIGINS.split(',').map(o => o.trim())
      : [];

    // In development, allow all origins
    if (process.env.NODE_ENV !== 'production') {
      return callback(null, true);
    }

    if (allowedOrigins.includes(origin) || allowedOrigins.includes('*')) {
      return callback(null, true);
    }

    return callback(new Error('Not allowed by CORS'));
  },
  credentials: true,
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
  allowedHeaders: [
    'Content-Type',
    'Authorization',
    'X-API-Key',
    'X-Request-Id',
    'Idempotency-Key'
  ],
  exposedHeaders: [
    'X-RateLimit-Limit',
    'X-RateLimit-Remaining',
    'X-RateLimit-Reset'
  ],
  maxAge: 86400 // 24 hours
};

app.use(cors(corsOptions));

// Compression
app.use(compression());

// =========================================================================
// BODY PARSING
// =========================================================================
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// =========================================================================
// REQUEST ID + LOGGING
// =========================================================================
app.use((req, res, next) => {
  // Generate or propagate request ID for tracing
  req.id = req.headers['x-request-id'] || require('crypto').randomUUID();
  res.setHeader('X-Request-Id', req.id);

  const startTime = Date.now();

  // Log when response finishes
  res.on('finish', () => {
    const duration = Date.now() - startTime;
    logger.info(`${req.method} ${req.originalUrl}`, {
      requestId: req.id,
      status: res.statusCode,
      duration: `${duration}ms`,
      ip: req.ip,
      userAgent: req.get('user-agent')
    });
  });

  next();
});

// =========================================================================
// RATE LIMITING (global)
// =========================================================================
app.use('/api', generalLimiter);

// =========================================================================
// HEALTH & INFO ENDPOINTS (no auth required)
// =========================================================================
app.get('/health', (req, res) => {
  const health = {
    status: 'healthy',
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    environment: process.env.NODE_ENV || 'development',
    version: process.env.npm_package_version || '1.0.0',
    services: {
      database: mongoose.connection.readyState === 1 ? 'connected' : 'disconnected'
    }
  };

  const isHealthy = health.services.database === 'connected';
  res.status(isHealthy ? 200 : 503).json(health);
});

app.get('/ready', async (req, res) => {
  try {
    // Check MongoDB
    if (mongoose.connection.readyState !== 1) {
      throw new Error('MongoDB not connected');
    }

    // Check Redis (optional — comment out if Redis is optional)
    try {
      const { getRedis } = require('./config/redis');
      const redis = getRedis();
      await redis.ping();
    } catch (redisErr) {
      logger.warn('Redis readiness check failed', { error: redisErr.message });
    }

    res.json({ status: 'ready' });
  } catch (error) {
    res.status(503).json({
      status: 'not ready',
      error: error.message
    });
  }
});

app.get('/api', (req, res) => {
  res.json({
    name: 'Third-Party API',
    version: '1.0.0',
    documentation: '/api/docs',
    health: '/health',
    endpoints: {
      auth: '/api/auth',
      users: '/api/users',
      data: '/api/data'
    }
  });
});

// =========================================================================
// API DOCUMENTATION (Swagger UI)
// =========================================================================
app.use(
  '/api/docs',
  swaggerUi.serve,
  swaggerUi.setup(swaggerSpec, {
    customCss: '.swagger-ui .topbar { display: none }',
    customSiteTitle: 'Third-Party API Docs',
    swaggerOptions: {
      persistAuthorization: true,
      displayRequestDuration: true,
      filter: true
    }
  })
);

// Expose raw OpenAPI spec
app.get('/api/docs.json', (req, res) => {
  res.setHeader('Content-Type', 'application/json');
  res.send(swaggerSpec);
});

// =========================================================================
// APPLICATION ROUTES
// =========================================================================
app.use('/api/auth', authRoutes);
app.use('/api/users', userRoutes);
app.use('/api/data', dataRoutes);

// =========================================================================
// 404 HANDLER (must be after all routes)
// =========================================================================
app.use((req, res, next) => {
  res.status(404).json({
    success: false,
    error: 'Endpoint not found',
    code: 'NOT_FOUND',
    path: req.originalUrl,
    method: req.method
  });
});

// =========================================================================
// GLOBAL ERROR HANDLER (must be last)
// =========================================================================
app.use(errorHandler);

// =========================================================================
// DATABASE + REDIS CONNECTION
// =========================================================================
const connectDB = async () => {
  try {
    const conn = await mongoose.connect(process.env.MONGODB_URI, {
      serverSelectionTimeoutMS: 5000,
      socketTimeoutMS: 45000
    });
    logger.info(`MongoDB connected: ${conn.connection.host}`);

    // Connection event listeners
    mongoose.connection.on('error', (err) => {
      logger.error('MongoDB connection error', { error: err.message });
    });

    mongoose.connection.on('disconnected', () => {
      logger.warn('MongoDB disconnected');
    });

    mongoose.connection.on('reconnected', () => {
      logger.info('MongoDB reconnected');
    });

    return conn;
  } catch (error) {
    logger.error('MongoDB initial connection failed', { error: error.message });
    throw error;
  }
};

const connectRedisClient = async () => {
  try {
    await connectRedis();
    logger.info('Redis connected');
  } catch (error) {
    logger.warn('Redis connection failed — rate limiting will fail-open', {
      error: error.message
    });
  }
};

// =========================================================================
// SERVER BOOTSTRAP
// =========================================================================
const PORT = process.env.PORT || 3000;
let server;

const startServer = async () => {
  try {
    // Connect to databases
    await connectDB();
    await connectRedisClient();

    // Start HTTP server
    server = app.listen(PORT, () => {
      logger.info(`Server running on port ${PORT} in ${process.env.NODE_ENV || 'development'} mode`);
      logger.info(`Health check:  http://localhost:${PORT}/health`);
      logger.info(`API docs:      http://localhost:${PORT}/api/docs`);
    });

    // Handle server errors
    server.on('error', (error) => {
      if (error.code === 'EADDRINUSE') {
        logger.error(`Port ${PORT} is already in use`);
      } else {
        logger.error('Server error', { error: error.message });
      }
      process.exit(1);
    });
  } catch (error) {
    logger.error('Failed to start server', { error: error.message, stack: error.stack });
    process.exit(1);
  }
};

// =========================================================================
// GRACEFUL SHUTDOWN
// =========================================================================
const shutdown = async (signal) => {
  logger.info(`${signal} received. Starting graceful shutdown...`);

  // Force exit after 30s if shutdown hangs
  const forceExit = setTimeout(() => {
    logger.error('Graceful shutdown timed out — forcing exit');
    process.exit(1);
  }, 30000);
  forceExit.unref();

  try {
    // Stop accepting new connections
    if (server) {
      await new Promise((resolve, reject) => {
        server.close((err) => (err ? reject(err) : resolve()));
      });
      logger.info('HTTP server closed');
    }

    // Close MongoDB
    if (mongoose.connection.readyState !== 0) {
      await mongoose.connection.close(false);
      logger.info('MongoDB connection closed');
    }

    // Close Redis
    try {
      const { getRedis } = require('./config/redis');
      const redis = getRedis();
      if (redis && redis.isOpen) {
        await redis.quit();
        logger.info('Redis connection closed');
      }
    } catch (err) {
      // Redis may not have been initialized
    }

    clearTimeout(forceExit);
    logger.info('Shutdown complete');
    process.exit(0);
  } catch (error) {
    logger.error('Error during shutdown', { error: error.message });
    process.exit(1);
  }
};

process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT', () => shutdown('SIGINT'));

// Catch unhandled errors
process.on('unhandledRejection', (reason) => {
  logger.error('Unhandled promise rejection', {
    reason: reason instanceof Error ? reason.message : String(reason),
    stack: reason instanceof Error ? reason.stack : undefined
  });
});

process.on('uncaughtException', (error) => {
  logger.error('Uncaught exception', {
    error: error.message,
    stack: error.stack
  });
  // Exit on uncaught exception — state is likely corrupted
  shutdown('uncaughtException');
});

// =========================================================================
// START SERVER (only if run directly, not when imported for tests)
// =========================================================================
if (require.main === module) {
  startServer();
}

module.exports = app;
module.exports.startServer = startServer;