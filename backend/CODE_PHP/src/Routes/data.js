const express = require('express');
const router = express.Router();
const dataController = require('../Controllers/dataController');
const { authenticate, requireScope } = require('../middleware/auth');
const { apiKeyLimiter } = require('../middleware/rateLimiter');
const { validate, validateQuery, schemas } = require('../middleware/validation');

// All data routes require authentication
router.use(authenticate);
router.use(apiKeyLimiter);

// Bulk route MUST come before /:id routes to avoid "bulk" being treated as an id
router.post(
  '/bulk',
  requireScope('write', 'admin'),
  validate(schemas.bulkCreate),
  dataController.bulkCreate
);

router.post(
  '/',
  requireScope('write', 'admin'),
  validate(schemas.createData),
  dataController.createData
);

router.get(
  '/',
  requireScope('read', 'write', 'admin'),
  validateQuery(schemas.pagination),
  dataController.getAllData
);

router.get(
  '/:id',
  requireScope('read', 'write', 'admin'),
  dataController.getDataById
);

router.patch(
  '/:id',
  requireScope('write', 'admin'),
  validate(schemas.updateData),
  dataController.updateData
);

router.delete(
  '/:id',
  requireScope('delete', 'admin'),
  dataController.deleteData
);

module.exports = router;