require('dotenv').config();

module.exports = {
    // Service Configuration
    port: process.env.ATTENDANCE_SERVICE_PORT || 3002,
    environment: process.env.NODE_ENV || 'development',

    // This service's own database for storing attendance records
    db: {
        host: process.env.ATTENDANCE_DB_HOST,
        user: process.env.ATTENDANCE_DB_USER,
        password: process.env.ATTENDANCE_DB_PASSWORD,
        database: process.env.ATTENDANCE_DB_NAME,
        port: process.env.ATTENDANCE_DB_PORT || 3306,
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
        logFile: 'src/logs/attendance.log',
        errorLogFile: 'src/logs/error.log',
    }
};
