const winston = require('winston');
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
        // File transport for all attendance logs
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

module.exports = logger;
