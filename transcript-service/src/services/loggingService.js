const winston = require('winston');
const fs = require('fs').promises;
const path = require('path');
const config = require('../../config/config');

const { combine, timestamp, printf, colorize, align } = winston.format;

// Custom log format
const logFormat = printf(({ level, message, timestamp }) => {
    return `${timestamp} ${level}: ${message}`;
});

const logger = winston.createLogger({
    level: config.logging.level,
    format: combine(
        timestamp({ format: 'YYYY-MM-DD HH:mm:ss' }),
        align(),
        logFormat
    ),
    transports: [
        // Console transport
        new winston.transports.Console({
            format: combine(
                colorize(),
                timestamp({ format: 'YYYY-MM-DD HH:mm:ss' }),
                align(),
                logFormat
            ),
        }),
        // File transport for all logs
        new winston.transports.File({
            filename: config.logging.logFile
        }),
        // File transport for error logs
        new winston.transports.File({
            filename: config.logging.errorLogFile,
            level: 'error'
        }),
    ],
    exceptionHandlers: [
        new winston.transports.File({ filename: config.logging.errorLogFile })
    ],
    rejectionHandlers: [
        new winston.transports.File({ filename: config.logging.errorLogFile })
    ]
});

/**
 * Retrieves the content of the main log file.
 * @returns {Promise<string>} The content of the log file.
 */
logger.getLogs = async () => {
    try {
        // Resolve the log file path from the config
        const logFilePath = path.resolve(config.logging.logFile);
        const data = await fs.readFile(logFilePath, 'utf8');
        return data;
    } catch (error) {
        logger.error(`Could not read log file: ${error.message}`);
        if (error.code === 'ENOENT') {
            // This is a special case where the log file might not have been created yet.
            return 'Log file does not exist yet. No logs to display.';
        }
        // For other errors (e.g., permissions), throw a generic error
        throw new Error('Failed to retrieve logs due to a server error.');
    }
};


module.exports = logger;
