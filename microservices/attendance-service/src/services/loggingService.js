const winston = require('winston');

const { combine, timestamp, printf, colorize, align } = winston.format;

// Custom log format
const logFormat = printf(({ level, message, timestamp }) => {
    return `${timestamp} ${level}: ${message}`;
});

const logger = winston.createLogger({
    level: process.env.LOG_LEVEL || 'info',
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
            filename: 'logs/attendance-service.log'
        }),
        // File transport for error logs
        new winston.transports.File({
            filename: 'logs/attendance-service-error.log',
            level: 'error'
        }),
    ],
    exceptionHandlers: [
        new winston.transports.File({ filename: 'logs/attendance-service-error.log' })
    ],
    rejectionHandlers: [
        new winston.transports.File({ filename: 'logs/attendance-service-error.log' })
    ]
});

module.exports = logger;
