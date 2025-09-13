const dotenv = require('dotenv');

/**
 * Load environment variables from a .env file in the project root.
 * By default, `dotenv.config()` will look for a .env file in the current
 * working directory, which is exactly what we want when the user runs
 * `npm start` from the `transcript-service` directory.
 */
dotenv.config();

/**
 * Application configuration object.
 * It reads values from environment variables, providing defaults for some.
 * This centralization of configuration makes the application easier to manage.
 */
const config = {
  // The port the server will listen on.
  port: process.env.PORT || 3000,

  // The method for connecting to the core system.
  // Valid options are 'DB' (direct database connection) or 'API' (REST API).
  connectionMode: process.env.CONNECTION_MODE || 'DB',

  // Configuration for the direct database connection.
  // These values are only used if connectionMode is 'DB'.
  database: {
    host: process.env.DB_HOST,
    port: process.env.DB_PORT,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    name: process.env.DB_NAME,
  },

  // Configuration for the core system's REST API.
  // These values are only used if connectionMode is 'API'.
  api: {
    baseUrl: process.env.CORE_API_BASE_URL,
    apiKey: process.env.CORE_API_KEY,
  },

  // Configuration for the logger.
  logging: {
    level: process.env.LOG_LEVEL || 'info',
  },
};

module.exports = config;
