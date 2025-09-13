require('dotenv').config();

// This service connects to multiple databases to perform its analysis.
// It does not have its own database in this initial implementation.

module.exports = {
    // Service Configuration
    port: process.env.AI_SERVICE_PORT || 3005,
    environment: process.env.NODE_ENV || 'development',

    // --- Database Connections ---

    // Connection to the CORE OpenEMIS database
    dbConnections: {
        core: {
            host: process.env.CORE_DB_HOST,
            user: process.env.CORE_DB_USER,
            password: process.env.CORE_DB_PASSWORD,
            database: process.env.CORE_DB_NAME,
            port: process.env.CORE_DB_PORT || 3306,
        },
        // Connection to the Attendance Service database
        attendance: {
            host: process.env.ATTENDANCE_DB_HOST,
            user: process.env.ATTENDANCE_DB_USER,
            password: process.env.ATTENDANCE_DB_PASSWORD,
            database: process.env.ATTENDANCE_DB_NAME,
            port: process.env.ATTENDANCE_DB_PORT || 3306,
        },
        // Connection to the Exam Service database
        exam: {
            host: process.env.EXAM_DB_HOST,
            user: process.env.EXAM_DB_USER,
            password: process.env.EXAM_DB_PASSWORD,
            database: process.env.EXAM_DB_NAME,
            port: process.env.EXAM_DB_PORT || 3306,
        }
    },

    // --- At-Risk Analysis Parameters ---
    analysis: {
        // Defines the score below which a student is considered at-risk from exams.
        examScoreThreshold: parseFloat(process.env.EXAM_SCORE_THRESHOLD) || 50.0,
        // Defines the number of unexcused absences to be considered at-risk.
        attendanceAbsenceThreshold: parseInt(process.env.ATTENDANCE_ABSENCE_THRESHOLD, 10) || 5,
    },

    // Logging Configuration
    logging: {
        level: process.env.LOG_LEVEL || 'info',
        logFile: 'src/logs/ai.log',
        errorLogFile: 'src/logs/error.log',
    }
};
