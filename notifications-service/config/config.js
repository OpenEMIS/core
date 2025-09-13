require('dotenv').config();

module.exports = {
    // Service Configuration
    port: process.env.NOTIFICATION_SERVICE_PORT || 3004,
    environment: process.env.NODE_ENV || 'development',

    // This service's own database for storing notification logs
    db: {
        host: process.env.NOTIFICATION_DB_HOST,
        user: process.env.NOTIFICATION_DB_USER,
        password: process.env.NOTIFICATION_DB_PASSWORD,
        database: process.env.NOTIFICATION_DB_NAME,
        port: process.env.NOTIFICATION_DB_PORT || 3306,
    },

    // Core System Connection (for fetching user contact info)
    coreDataMode: process.env.CORE_DATA_MODE || 'api',
    coreDb: {
        host: process.env.CORE_DB_HOST,
        user: process.env.CORE_DB_USER,
        password: process.env.CORE_DB_PASSWORD,
        database: process.env.CORE_DB_NAME,
        port: process.env.CORE_DB_PORT || 3306,
    },
    coreApi: {
        baseUrl: process.env.CORE_API_BASE_URL,
        apiKey: process.env.CORE_API_KEY,
    },

    // Email (Nodemailer) Configuration
    email: {
        host: process.env.EMAIL_HOST,
        port: process.env.EMAIL_PORT,
        secure: process.env.EMAIL_SECURE === 'true', // true for 465, false for other ports
        auth: {
            user: process.env.EMAIL_USER,
            pass: process.env.EMAIL_PASS,
        },
        from: process.env.EMAIL_FROM || '"OpenEMIS Notifier" <no-reply@openemis.org>'
    },

    // SMS (Twilio) Configuration
    sms: {
        accountSid: process.env.TWILIO_ACCOUNT_SID,
        authToken: process.env.TWILIO_AUTH_TOKEN,
        fromNumber: process.env.TWILIO_FROM_NUMBER,
    },

    // Logging Configuration
    logging: {
        level: process.env.LOG_LEVEL || 'info',
        logFile: 'src/logs/notifications.log',
        errorLogFile: 'src/logs/error.log',
    }
};
