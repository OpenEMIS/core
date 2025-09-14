const mysql = require('mysql2/promise');
const config = require('../../config/config');
const logger = require('./loggingService');

const { core, attendance, exam } = config.dbConnections;

let corePool;
let attendancePool;
let examPool;

/**
 * Initializes connection pools for all required databases.
 */
const initializePools = async () => {
    try {
        logger.info('Initializing database connection pools...');

        corePool = mysql.createPool(core);
        await corePool.getConnection(); // Test connection
        logger.info('Data connector successfully connected to Core DB.');

        attendancePool = mysql.createPool(attendance);
        await attendancePool.getConnection(); // Test connection
        logger.info('Data connector successfully connected to Attendance DB.');

        examPool = mysql.createPool(exam);
        await examPool.getConnection(); // Test connection
        logger.info('Data connector successfully connected to Exam DB.');

        logger.info('All database pools initialized successfully.');

    } catch (error) {
        logger.error(`Failed to initialize one or more database pools: ${error.message}`);
        // Exit the process if any connection fails, as the service cannot function.
        process.exit(1);
    }
};

// Initialize pools when the service starts
initializePools();

// Export the pools for other services to use.
// A more advanced implementation might wrap these in functions that handle query logic.
module.exports = {
    getCorePool: () => corePool,
    getAttendancePool: () => attendancePool,
    getExamPool: () => examPool,
};
