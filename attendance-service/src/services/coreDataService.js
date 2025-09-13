const axios = require('axios');
const mysql = require('mysql2/promise');
const config = require('../../config/config');
const logger = require('./loggingService');

const { coreDataMode, coreDb: dbConfig, coreApi: apiConfig } = config;

let pool;

/**
 * Initializes the database connection pool to the CORE database if in 'db' mode.
 */
const initializeDb = async () => {
    try {
        pool = mysql.createPool(dbConfig);
        await pool.getConnection(); // Test the connection
        logger.info('Successfully connected to the core database for validation.');
    } catch (error) {
        logger.error(`Failed to connect to the core database: ${error.message}`);
        process.exit(1);
    }
};

/**
 * Validates that a student and class exist in the core system's database.
 * @param {string} studentId The ID of the student.
 * @param {string} classId The ID of the class.
 * @returns {Promise<boolean>} True if both exist, false otherwise.
 */
const validateRecordWithDb = async (studentId, classId) => {
    // For simplicity, we check for the student's existence.
    // A full implementation would also check the class and the student's enrollment in that class.
    const query = 'SELECT id FROM students WHERE id = ?';
    try {
        const [rows] = await pool.query(query, [studentId]);
        if (rows.length === 0) {
            logger.warn(`Validation failed: Student with ID ${studentId} not found in core DB.`);
            return false;
        }
        return true;
    } catch (error) {
        logger.error(`DB validation error for studentId ${studentId}: ${error.message}`);
        throw new Error('Failed to validate record against core database.');
    }
};

/**
 * Validates that a student and class exist using the core system's API.
 * @param {string} studentId The ID of the student.
 * @param {string} classId The ID of the class.
 * @returns {Promise<boolean>} True if both exist, false otherwise.
 */
const validateRecordWithApi = async (studentId, classId) => {
    const url = `${apiConfig.baseUrl}students/${studentId}`;
    try {
        await axios.get(url, {
            headers: { 'Authorization': `Bearer ${apiConfig.apiKey}` }
        });
        // If the request succeeds, the student exists.
        // A full implementation would also check the class.
        return true;
    } catch (error) {
        if (error.response && error.response.status === 404) {
            logger.warn(`Validation failed: Student with ID ${studentId} not found via core API.`);
            return false;
        }
        logger.error(`API validation error for studentId ${studentId}: ${error.message}`);
        throw new Error('Failed to validate record against core API.');
    }
};

// Initialize based on the selected mode
if (coreDataMode === 'db') {
    initializeDb();
} else if (coreDataMode === 'api') {
    logger.info('Core data validation service is running in API mode.');
} else {
    logger.error('Invalid CORE_DATA_MODE specified in configuration. Please use "db" or "api".');
    process.exit(1);
}

module.exports = {
    validateRecord: coreDataMode === 'db' ? validateRecordWithDb : validateRecordWithApi,
};
