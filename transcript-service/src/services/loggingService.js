const winston = require('winston');
const config = require('../../config/config');

/**
 * A centralized logger for the application using the Winston library.
 *
 * It is configured to:
 * - Log to the console.
 * - Colorize output for better readability.
 * - Include timestamps in a standard format.
 * - Read the minimum log level (e.g., 'info', 'debug') from the application configuration.
 *
 * In a production environment, you would typically add file transports
 * to write logs to persistent storage.
 */
const logger = winston.createLogger({
  level: config.logging.level,
  format: winston.format.combine(
    winston.format.timestamp({
      format: 'YYYY-MM-DD HH:mm:ss'
    }),
    winston.format.errors({ stack: true }), // Log the full stack trace for errors
    winston.format.splat(),
    winston.format.json()
  ),
  transports: [
    // All logs will be written to the console.
    new winston.transports.Console({
      format: winston.format.combine(
        winston.format.colorize(),
        winston.format.printf(
          ({ level, message, timestamp, stack }) => {
            if (stack) {
              // Print stack trace for errors
              return `${timestamp} ${level}: ${message}\n${stack}`;
            }
            return `${timestamp} ${level}: ${message}`;
          }
        )
      ),
    }),
    // Example of how you might add file logging for production:
    // new winston.transports.File({ filename: 'logs/error.log', level: 'error' }),
    // new winston.transports.File({ filename: 'logs/combined.log' })
  ],
});

module.exports = logger;
