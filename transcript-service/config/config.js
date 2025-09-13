const dotenv = require('dotenv');
const path = require('path');

// Load environment variables from the .env file in the project root.
dotenv.config();

/**
 * Application configuration module.
 *
 * This module reads environment variables from the .env file,
 * provides default values for some settings, and exports a
 * configuration object for use throughout the application.
 * This centralizes configuration and keeps sensitive data
 * out of the codebase.
 */
const config = {
  // The port the server will listen on.
  port: process.env.PORT || 3000,

  // The method for connecting to the core system ('DB' or 'API').
  connectionMode: process.env.CONNECTION_MODE || 'DB',

  // Database connection details.
  database: {
    host: process.env.CORE_DB_HOST,
    user: process.env.CORE_DB_USER,
    password: process.env.CORE_DB_PASSWORD,
    name: process.env.CORE_DB_NAME,
    port: process.env.CORE_DB_PORT || 3306,
  },

  // Core system API details.
  api: {
    baseUrl: process.env.CORE_API_URL,
    apiKey: process.env.CORE_API_KEY,
  },

  // Logging configuration.
  logging: {
    level: process.env.LOG_LEVEL || 'info',
  },

  /**
   * The absolute path to the directory for output files (e.g., logs).
   * It resolves the path relative to the project's root directory
   * to prevent issues with where the start script is called from.
   */
  outputDir: path.resolve(process.cwd(), process.env.OUTPUT_DIR || 'output'),
};

module.exports = config;
