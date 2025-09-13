const winston = require('winston');
require('winston-daily-rotate-file');
const path = require('path');
const fs = require('fs');
const config = require('../../config/config');

// Ensure the log directory from the configuration exists.
const logDir = config.outputDir;
if (!fs.existsSync(logDir)) {
  console.log(`Log directory not found. Creating: ${logDir}`);
  fs.mkdirSync(logDir, { recursive: true });
}

/**
 * A transport for rotating log files daily.
 * This is configured to store logs in the directory specified in the .env file.
 * Logs are stored in a structured JSON format to be easily machine-readable,
 * which is essential for the /logs API endpoint.
 */
const fileRotateTransport = new winston.transports.DailyRotateFile({
  filename: path.join(logDir, 'transcript-events-%DATE%.log'),
  datePattern: 'YYYY-MM-DD',
  zippedArchive: true, // Compress old log files
  maxSize: '20m',      // Rotate if file size exceeds 20MB
  maxFiles: '14d',     // Keep logs for 14 days
  format: winston.format.combine(
    winston.format.timestamp(),
    winston.format.json()
  ),
});

/**
 * The main application logger.
 * It is configured with two transports:
 * 1. Console: For development visibility, with a simple, colorized format.
 * 2. DailyRotateFile: For persistent, structured JSON logging to a file.
 */
const logger = winston.createLogger({
  level: config.logging.level, // Log level from config (e.g., 'info', 'debug')
  format: winston.format.combine(
    winston.format.errors({ stack: true }), // Log the full stack trace for errors
    winston.format.splat()
  ),
  transports: [
    new winston.transports.Console({
      format: winston.format.combine(
        winston.format.colorize(),
        winston.format.timestamp({ format: 'YYYY-MM-DD HH:mm:ss' }),
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
    fileRotateTransport, // Add the file transport
  ],
  exitOnError: false, // Do not exit on handled exceptions
});

/**
 * A dedicated function for logging transcript generation events.
 * This ensures that all such events are logged in a consistent, structured format
 * that can be easily queried and returned by the /logs API endpoint.
 *
 * @param {object} details - The details of the event.
 * @param {string} details.studentId - The ID of the student.
 * @param {string} details.format - The requested format (e.g., 'json', 'pdf').
 * @param {string} details.status - The outcome (e.g., 'SUCCESS', 'NOT_FOUND', 'ERROR').
 * @param {string} [details.message] - An optional message.
 */
const logTranscriptEvent = (details) => {
  logger.info({
    eventType: 'TRANSCRIPT_GENERATION',
    ...details,
  });
};

module.exports = {
  logger,
  logTranscriptEvent,
};
