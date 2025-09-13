require('dotenv').config();

module.exports = {
    // Service Configuration
    port: process.env.EXAM_SERVICE_PORT || 3003,
    environment: process.env.NODE_ENV || 'development',

    // This service's own database for storing exam-related data
    db: {
        host: process.env.EXAM_DB_HOST,
        user: process.env.EXAM_DB_USER,
        password: process.env.EXAM_DB_PASSWORD,
        database: process.env.EXAM_DB_NAME,
        port: process.env.EXAM_DB_PORT || 3306,
    },

    // Core System Connection (for validating students, classes, etc.)
    // 'db' or 'api'
    coreDataMode: process.env.CORE_DATA_MODE || 'api',

    // Core Database Configuration (if CORE_DATA_MODE is 'db')
    coreDb: {
        host: process.env.CORE_DB_HOST,
        user: process.env.CORE_DB_USER,
        password: process.env.CORE_DB_PASSWORD,
        database: process.env.CORE_DB_NAME,
        port: process.env.CORE_DB_PORT || 3306,
    },

    // Core API Configuration (if CORE_DATA_MODE is 'api')
    coreApi: {
        baseUrl: process.env.CORE_API_BASE_URL,
        apiKey: process.env.CORE_API_KEY,
    },

    // Logging Configuration
    logging: {
        level: process.env.LOG_LEVEL || 'info',
        logFile: 'src/logs/exam.log',
        errorLogFile: 'src/logs/error.log',
    }
};
