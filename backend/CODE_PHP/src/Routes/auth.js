const express = require('express');
const router = express.Router();
const authController = require('../controllers/authController');
const { authenticate } = require('../middleware/auth');
const { validate, schemas } = require('../middleware/validation');
const { authLimiter } = require('../middleware/rateLimiter');

// Public routes
router.post('/register', authLimiter, validate(schemas.register), authController.register);
router.post('/login', authLimiter, validate(schemas.login), authController.login);

// Protected routes
router.get('/me', authenticate, authController.me);

// API key management
router.post('/api-keys', authenticate, authController.createApiKey);
router.get('/api-keys', authenticate, authController.listApiKeys);
router.delete('/api-keys/:id', authenticate, authController.revokeApiKey);

module.exports = router;