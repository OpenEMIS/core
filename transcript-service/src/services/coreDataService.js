const mysql = require('mysql2/promise');
const axios = require('axios');
const config = require('../../config/config');
const logger = require('./loggingService');

// --- Database Connection (Singleton Pool) ---

let dbPool;

/**
 * Initializes and returns a singleton MySQL connection pool.
 * This prevents creating a new connection for every request, which is more efficient.
 * The connection details are read from the central configuration.
 * @returns {object} The MySQL connection pool.
 */
const getDbPool = () => {
  if (!dbPool) {
    try {
      logger.info('Initializing new database connection pool...');
      dbPool = mysql.createPool({
        host: config.database.host,
        user: config.database.user,
        password: config.database.password,
        database: config.database.name,
        port: config.database.port,
        waitForConnections: true,
        connectionLimit: 10, // Max number of connections in the pool
        queueLimit: 0, // No limit on the number of queued connection requests
      });
      logger.info('Database pool initialized successfully.');
    } catch (error) {
      logger.error('Failed to initialize database pool:', error);
      throw error; // Re-throw to prevent the application from running with a bad DB config
    }
  }
  return dbPool;
};

// --- Database Implementation ---

/**
 * Fetches student transcript data directly from the core database.
 * NOTE: These queries are examples and MUST be adapted to your actual database schema.
 * @param {string} studentId - The ID of the student.
 * @returns {Promise<object|null>} The student's data or null if not found.
 */
const getStudentDataFromDb = async (studentId) => {
  logger.debug(`Querying database for student ID: ${studentId}`);
  const pool = getDbPool();

  // IMPORTANT: The table and column names (e.g., 'students', 'grades', 'courses')
  // are assumptions. You must update these queries to match your schema.
  const [studentRows] = await pool.query('SELECT id, name as studentName, student_id as studentIdentifier FROM students WHERE id = ?', [studentId]);
  if (studentRows.length === 0) {
    return null;
  }

  const [gradeRows] = await pool.query(
    'SELECT c.name as courseName, g.grade, c.credits FROM grades g JOIN courses c ON g.course_id = c.id WHERE g.student_id = ? ORDER BY c.year, c.semester',
    [studentId]
  );

  // In a real scenario, you would calculate GPA and other summary statistics here.
  const summary = {
    totalCredits: gradeRows.reduce((acc, row) => acc + row.credits, 0),
    gpa: 'N/A', // Placeholder for GPA calculation
  };

  return {
    studentInfo: studentRows[0],
    grades: gradeRows,
    summary,
  };
};

// --- API Implementation ---

/**
 * Fetches student transcript data from the core system's REST API.
 * @param {string} studentId - The ID of the student.
 * @returns {Promise<object|null>} The student's data or null if not found.
 */
const getStudentDataFromApi = async (studentId) => {
  const apiUrl = `${config.api.baseUrl}/students/${studentId}/transcript`;
  logger.debug(`Calling core API for student ID ${studentId} at: ${apiUrl}`);

  try {
    const response = await axios.get(apiUrl, {
      headers: { 'X-API-Key': config.api.apiKey },
      timeout: 5000, // 5-second timeout
    });
    // Assuming the API returns data in the exact structure needed by the other services.
    return response.data;
  } catch (error) {
    if (error.response && error.response.status === 404) {
      logger.warn(`Core API returned 404 for student ID: ${studentId}`);
      return null;
    }
    logger.error('Error fetching data from core API:', error.message);
    // Re-throw a more generic error to avoid leaking implementation details.
    throw new Error('An error occurred while communicating with the core API.');
  }
};

// --- Main Exported Function ---

/**
 * Fetches transcript data for a specific student.
 * This function acts as a dispatcher, calling either the DB or API implementation
 * based on the `CONNECTION_MODE` environment variable.
 * @param {string} studentId - The unique identifier for the student.
 * @returns {Promise<object|null>} A promise that resolves with the student's data, or null if not found.
 */
const getStudentData = async (studentId) => {
  if (config.connectionMode.toUpperCase() === 'API') {
    logger.info('Fetching data via API mode.');
    return getStudentDataFromApi(studentId);
  }

  // Default to DB connection
  logger.info('Fetching data via DB mode.');
  return getStudentDataFromDb(studentId);
};

module.exports = {
  getStudentData,
};
