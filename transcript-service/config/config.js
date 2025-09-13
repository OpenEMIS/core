require('dotenv').config();

module.exports = {
    // Service Configuration
    port: process.env.TRANSCRIPT_SERVICE_PORT || 3001,
    environment: process.env.NODE_ENV || 'development',

    // Core System Connection
    // 'db' or 'api'
    coreDataMode: process.env.CORE_DATA_MODE || 'api',

    // Database Configuration (if CORE_DATA_MODE is 'db')
    db: {
        host: process.env.CORE_DB_HOST,
        user: process.env.CORE_DB_USER,
        password: process.env.CORE_DB_PASSWORD,
        database: process.env.CORE_DB_NAME,
        port: process.env.CORE_DB_PORT || 3306,
    },

    // API Configuration (if CORE_DATA_MODE is 'api')
    api: {
        baseUrl: process.env.CORE_API_BASE_URL,
        apiKey: process.env.CORE_API_KEY,
    },

    // Logging Configuration
    logging: {
        level: process.env.LOG_LEVEL || 'info',
        logFile: 'src/logs/service.log',
        errorLogFile: 'src/logs/error.log',
    }
};
