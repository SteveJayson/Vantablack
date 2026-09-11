const Data = require('../models/Data');

class DataService {
  static async listForUser(userId, filters = {}) {
    const { page = 1, limit = 20, sort = '-createdAt', search, type, tags } = filters;

    const query = {
      $or: [{ owner: userId }, { isPublic: true }]
    };

    if (type) query.type = type;
    if (tags) {
      const tagList = Array.isArray(tags) ? tags : tags.split(',');
      query.tags = { $in: tagList };
    }
    if (search) query.$text = { $search: search };

    const skip = (page - 1) * limit;
    const [items, total] = await Promise.all([
      Data.find(query).skip(skip).limit(limit).sort(sort).populate('owner', 'name email'),
      Data.countDocuments(query)
    ]);

    return {
      items,
      pagination: {
        page: Number(page),
        limit: Number(limit),
        total,
        pages: Math.ceil(total / limit)
      }
    };
  }

  static async create(userId, payload) {
    return Data.create({ ...payload, owner: userId });
  }

  static async findAccessible(id, userId, role) {
    const doc = await Data.findById(id).populate('owner', 'name email');
    if (!doc) return null;
    const isOwner = doc.owner._id.toString() === userId.toString();
    if (!isOwner && !doc.isPublic && role !== 'admin') return null;
    return doc;
  }
}

module.exports = DataService;