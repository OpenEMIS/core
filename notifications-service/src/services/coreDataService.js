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
        await pool.getConnection();
        logger.info('Successfully connected to the core database for contact retrieval.');
    } catch (error) {
        logger.error(`Failed to connect to the core database: ${error.message}`);
        process.exit(1);
    }
};

/**
 * Fetches a user's contact info from the core system's database.
 * @param {string} userId The ID of the user (could be a student, staff, etc.).
 * @returns {Promise<object|null>} An object with email and phone, or null if not found.
 */
const getUserContactInfoFromDb = async (userId) => {
    // This query assumes a 'users' table with 'email' and 'phone' columns.
    // This would need to be adapted to the actual OpenEMIS schema.
    const query = 'SELECT email, phone FROM users WHERE id = ?';
    try {
        const [rows] = await pool.query(query, [userId]);
        if (rows.length === 0) {
            logger.warn(`Could not find contact info for userId ${userId} in core DB.`);
            return null;
        }
        return { email: rows[0].email, phone: rows[0].phone };
    } catch (error) {
        logger.error(`DB error fetching contact info for userId ${userId}: ${error.message}`);
        throw new Error('Failed to retrieve contact info from core database.');
    }
};

/**
 * Fetches a user's contact info using the core system's API.
 * @param {string} userId The ID of the user.
 * @returns {Promise<object|null>} An object with email and phone, or null if not found.
 */
const getUserContactInfoFromApi = async (userId) => {
    const url = `${apiConfig.baseUrl}users/${userId}`; // Assuming a /users/:id endpoint
    try {
        const response = await axios.get(url, {
            headers: { 'Authorization': `Bearer ${apiConfig.apiKey}` }
        });
        // Assuming the API response has a data object with user details
        const { email, phone } = response.data;
        return { email, phone };
    } catch (error) {
        if (error.response && error.response.status === 404) {
            logger.warn(`Could not find contact info for userId ${userId} via core API.`);
            return null;
        }
        logger.error(`API error fetching contact info for userId ${userId}: ${error.message}`);
        throw new Error('Failed to retrieve contact info from core API.');
    }
};

// Initialize based on the selected mode
if (coreDataMode === 'db') {
    initializeDb();
} else if (coreDataMode === 'api') {
    logger.info('Core data service for contact retrieval is running in API mode.');
} else {
    logger.error('Invalid CORE_DATA_MODE specified in configuration. Please use "db" or "api".');
    process.exit(1);
}

module.exports = {
    getUserContactInfo: coreDataMode === 'db' ? getUserContactInfoFromDb : getUserContactInfoFromApi,
};
