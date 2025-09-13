const axios = require('axios');
const mysql = require('mysql2/promise');
const config = require('../../config/config');
const logger = require('./loggingService');

const { coreDataMode, db: dbConfig, api: apiConfig } = config;

let pool;

/**
 * Initializes the database connection pool if in 'db' mode.
 */
const initializeDb = async () => {
    try {
        pool = mysql.createPool(dbConfig);
        await pool.getConnection(); // Test the connection
        logger.info('Successfully connected to the core database.');
    } catch (error) {
        logger.error(`Failed to connect to the core database: ${error.message}`);
        // Exit the process if the DB connection fails, as the service cannot function.
        process.exit(1);
    }
};

/**
 * Fetches student data from the core system's database.
 * @param {string} studentId The ID of the student.
 * @returns {Promise<object|null>} The student's data or null if not found.
 */
const getStudentDataFromDb = async (studentId) => {
    let connection;
    try {
        connection = await pool.getConnection();

        // 1. Fetch student's basic information
        // This query is based on a plausible schema for OpenEMIS.
        const studentQuery = `
            SELECT
                s.id AS student_id,
                s.first_name,
                s.last_name,
                s.date_of_birth,
                i.name AS institution_name,
                c.name AS class_name
            FROM students s
            LEFT JOIN institutions i ON s.institution_id = i.id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE s.id = ?;
        `;
        const [studentRows] = await connection.query(studentQuery, [studentId]);

        if (studentRows.length === 0) {
            return null; // Return null if student is not found
        }
        const studentData = studentRows[0];

        // 2. Fetch student's grades
        // ASSUMPTION: A 'student_grades' table exists with this structure.
        // This would need to be adapted to the actual schema.
        const gradesQuery = `
            SELECT
                course_name,
                grade,
                academic_year
            FROM student_grades
            WHERE student_id = ?
            ORDER BY academic_year DESC, course_name ASC;
        `;
        const [gradeRows] = await connection.query(gradesQuery, [studentId]);

        // 3. Combine data and return
        studentData.courses = gradeRows;
        return studentData;

    } catch (error) {
        logger.error(`Error fetching student data from DB for studentId ${studentId}: ${error.message}`);
        throw error; // Re-throw other database errors
    } finally {
        if (connection) connection.release();
    }
};

/**
 * Fetches student data from the core system's API.
 * @param {string} studentId The ID of the student.
 * @returns {Promise<object|null>} The student's data or null if not found.
 */
const getStudentDataFromApi = async (studentId) => {
    const url = `${apiConfig.baseUrl}students/${studentId}`;
    try {
        const response = await axios.get(url, {
            headers: {
                'Authorization': `Bearer ${apiConfig.apiKey}`,
                'Content-Type': 'application/json'
            }
        });
        return response.data;
    } catch (error) {
        if (error.response && error.response.status === 404) {
            logger.warn(`Student with ID ${studentId} not found via API.`);
            return null; // Return null if the API returns a 404
        }
        logger.error(`Error fetching student data from API for studentId ${studentId}: ${error.response?.status} ${error.response?.data?.message || error.message}`);
        // For other errors (e.g., 500, network error), throw a generic error
        throw new Error('Failed to fetch student data from core API.');
    }
};

// Initialize based on the selected mode
if (coreDataMode === 'db') {
    initializeDb();
} else if (coreDataMode === 'api') {
    logger.info('Core data service is running in API mode.');
} else {
    logger.error('Invalid CORE_DATA_MODE specified in configuration. Please use "db" or "api".');
    process.exit(1);
}

module.exports = {
    getStudentData: coreDataMode === 'db' ? getStudentDataFromDb : getStudentDataFromApi,
};
