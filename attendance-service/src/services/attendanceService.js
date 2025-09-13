const mysql = require('mysql2/promise');
const config = require('../../config/config');
const logger = require('./loggingService');
const coreDataService = require('./coreDataService');

let pool;

/**
 * Initializes this service's own database connection pool.
 */
const initializeDb = async () => {
    try {
        // Using the 'db' config which points to this service's own database
        pool = mysql.createPool(config.db);
        await pool.getConnection();
        logger.info('Attendance service successfully connected to its database.');
    } catch (error) {
        logger.error(`Attendance service failed to connect to its database: ${error.message}`);
        process.exit(1);
    }
};

// Initialize the database connection when the service starts
initializeDb();

/**
 * Marks attendance for a student after validating the student ID.
 * @param {object} attendanceData - Contains student_id, class_id, attendance_date, status.
 * @returns {Promise<object>} The newly created or updated attendance record.
 */
const markAttendance = async (attendanceData) => {
    const { student_id, class_id, attendance_date, status, notes } = attendanceData;

    // 1. Validate student exists in the core system before proceeding.
    const isValid = await coreDataService.validateRecord(student_id, class_id);
    if (!isValid) {
        const error = new Error('Validation failed: Student or class ID is not valid.');
        error.statusCode = 400; // Bad Request
        throw error;
    }

    // 2. Insert or update the record in this service's database.
    // The UQ constraint on (student_id, class_id, attendance_date) allows us to use
    // ON DUPLICATE KEY UPDATE to make this operation idempotent for a given day.
    const query = `
        INSERT INTO attendance_records (student_id, class_id, attendance_date, status, notes)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes);
    `;
    try {
        const [result] = await pool.query(query, [student_id, class_id, attendance_date, status, notes || null]);
        logger.info(`Attendance marked for student ${student_id} with status '${status}' on ${attendance_date}.`);
        // If it was an insert, result.insertId will be the new ID.
        // If it was an update, we don't get a new ID, but the record is updated.
        return { success: true, student_id, class_id, attendance_date, status };
    } catch (error) {
        logger.error(`Error in markAttendance for student ${student_id}: ${error.message}`);
        throw new Error('Failed to save attendance record to the database.');
    }
};

/**
 * Retrieves all attendance records for a specific student.
 * @param {string} studentId The ID of the student.
 * @returns {Promise<Array>} A list of attendance records.
 */
const getAttendanceForStudent = async (studentId) => {
    const query = 'SELECT * FROM attendance_records WHERE student_id = ? ORDER BY attendance_date DESC;';
    try {
        const [rows] = await pool.query(query, [studentId]);
        return rows;
    } catch (error) {
        logger.error(`Error retrieving attendance for student ${studentId}: ${error.message}`);
        throw new Error('Failed to retrieve student attendance.');
    }
};

/**
 * Retrieves all attendance records for a specific class on a given date.
 * @param {string} classId The ID of the class.
 * @param {string} date The date to retrieve records for (YYYY-MM-DD).
 * @returns {Promise<Array>} A list of attendance records.
 */
const getAttendanceForClass = async (classId, date) => {
    if (!date) {
        const error = new Error('A `date` query parameter is required to fetch class attendance.');
        error.statusCode = 400;
        throw error;
    }

    const query = 'SELECT * FROM attendance_records WHERE class_id = ? AND attendance_date = ?;';
    try {
        const [rows] = await pool.query(query, [classId, date]);
        return rows;
    } catch (error) {
        logger.error(`Error retrieving attendance for class ${classId} on ${date}: ${error.message}`);
        throw new Error('Failed to retrieve class attendance.');
    }
};

module.exports = {
    markAttendance,
    getAttendanceForStudent,
    getAttendanceForClass,
};
