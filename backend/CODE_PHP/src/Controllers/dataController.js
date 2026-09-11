// Get data by ID (continued)
const getDataById = async (req, res, next) => {
  try {
    const data = await Data.findById(req.params.id)
      .populate('owner', 'name email');

    if (!data) {
      return res.status(404).json({
        success: false,
        error: 'Data not found',
        code: ERROR_CODES.NOT_FOUND
      });
    }

    // Access control: owner or public
    const isOwner = data.owner._id.toString() === req.user._id.toString();
    if (!isOwner && !data.isPublic && req.user.role !== 'admin') {
      return res.status(403).json({
        success: false,
        error: 'Access denied',
        code: ERROR_CODES.FORBIDDEN
      });
    }

    res.json({ success: true, data });
  } catch (error) {
    next(error);
  }
};

// Update data
const updateData = async (req, res, next) => {
  try {
    const data = await Data.findById(req.params.id);

    if (!data) {
      return res.status(404).json({
        success: false,
        error: 'Data not found',
        code: ERROR_CODES.NOT_FOUND
      });
    }

    // Only owner or admin can update
    const isOwner = data.owner.toString() === req.user._id.toString();
    if (!isOwner && req.user.role !== 'admin') {
      return res.status(403).json({
        success: false,
        error: 'Access denied',
        code: ERROR_CODES.FORBIDDEN
      });
    }

    // Increment version on update
    const updatePayload = { ...req.body, $inc: { version: 1 } };
    const updated = await Data.findByIdAndUpdate(
      req.params.id,
      updatePayload,
      { new: true, runValidators: true }
    );

    res.json({ success: true, data: updated });
  } catch (error) {
    next(error);
  }
};

// Delete data
const deleteData = async (req, res, next) => {
  try {
    const data = await Data.findById(req.params.id);

    if (!data) {
      return res.status(404).json({
        success: false,
        error: 'Data not found',
        code: ERROR_CODES.NOT_FOUND
      });
    }

    const isOwner = data.owner.toString() === req.user._id.toString();
    if (!isOwner && req.user.role !== 'admin') {
      return res.status(403).json({
        success: false,
        error: 'Access denied',
        code: ERROR_CODES.FORBIDDEN
      });
    }

    await data.deleteOne();
    res.json({ success: true, message: 'Data deleted successfully' });
  } catch (error) {
    next(error);
  }
};

// Bulk create
const bulkCreate = async (req, res, next) => {
  try {
    const { items } = req.body;

    if (!Array.isArray(items) || items.length === 0) {
      return res.status(400).json({
        success: false,
        error: 'items must be a non-empty array',
        code: ERROR_CODES.VALIDATION_ERROR
      });
    }

    if (items.length > 100) {
      return res.status(400).json({
        success: false,
        error: 'Maximum 100 items per bulk request',
        code: ERROR_CODES.VALIDATION_ERROR
      });
    }

    const docs = items.map(item => ({ ...item, owner: req.user._id }));
    const created = await Data.insertMany(docs);

    res.status(201).json({
      success: true,
      count: created.length,
      data: created
    });
  } catch (error) {
    next(error);
  }
};

module.exports = {
  createData,
  getAllData,
  getDataById,
  updateData,
  deleteData,
  bulkCreate
};