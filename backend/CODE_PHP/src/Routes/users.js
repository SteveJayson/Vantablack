const express = require('express');
const router = express.Router();
const userController = require('../controllers/userController');
const { authenticate, authorize } = require('../middleware/auth');
const { validateQuery, schemas } = require('../middleware/validation');

// All user routes require authentication
router.use(authenticate);

router.get('/', authorize('admin'), validateQuery(schemas.pagination), userController.getAllUsers);
router.get('/:id', userController.getUserById);
router.patch('/:id', userController.updateUser);
router.delete('/:id', authorize('admin'), userController.deleteUser);

module.exports = router;