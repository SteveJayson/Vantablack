const User = require('../models/User');
const ApiKey = require('../models/ApiKey');
const jwt = require('jsonwebtoken');

class AuthService {
  static generateToken(userId) {
    return jwt.sign({ userId }, process.env.JWT_SECRET, {
      expiresIn: process.env.JWT_EXPIRES_IN || '7d'
    });
  }

  static async registerUser({ email, password, name }) {
    const existing = await User.findOne({ email });
    if (existing) {
      const error = new Error('Email already registered');
      error.statusCode = 409;
      error.code = 'DUPLICATE_ERROR';
      throw error;
    }
    return User.create({ email, password, name });
  }

  static async validateCredentials(email, password) {
    const user = await User.findOne({ email }).select('+password');
    if (!user) return null;
    const isValid = await user.comparePassword(password);
    return isValid ? user : null;
  }

  static async createApiKey(userId, { name, scopes, expiresInDays }) {
    const { key, hashedKey } = ApiKey.generateKey();
    const expiresAt = expiresInDays
      ? new Date(Date.now() + expiresInDays * 86400000)
      : undefined;

    const apiKey = await ApiKey.create({
      key, hashedKey, name, user: userId, scopes, expiresAt
    });

    return { apiKey, plainKey: key };
  }
}

module.exports = AuthService;