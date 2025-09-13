const attendanceService = require('../services/attendanceService');
const logger = require('../services/loggingService');

/**
 * Handles the creation of a new attendance record.
 */
const markAttendance = async (req, res, next) => {
    try {
        const { student_id, class_id, attendance_date, status, notes } = req.body;

        // Basic validation
        if (!student_id || !class_id || !attendance_date || !status) {
            return res.status(400).json({ error: 'Missing required fields: student_id, class_id, attendance_date, status.' });
        }

        const result = await attendanceService.markAttendance({ student_id, class_id, attendance_date, status, notes });
        res.status(201).json({ message: 'Attendance marked successfully.', data: result });
    } catch (error) {
        if (error.statusCode) {
            return res.status(error.statusCode).json({ error: error.message });
        }
        next(error); // Forward to global error handler
    }
};

/**
 * Handles retrieving attendance for a single student.
 */
const getStudentAttendance = async (req, res, next) => {
    try {
        const { studentId } = req.params;
        const records = await attendanceService.getAttendanceForStudent(studentId);
        res.status(200).json({ data: records });
    } catch (error) {
        next(error);
    }
};

/**
 * Handles retrieving attendance for an entire class on a specific date.
 */
const getClassAttendance = async (req, res, next) => {
    try {
        const { classId } = req.params;
        const { date } = req.query; // Expects date in YYYY-MM-DD format

        const records = await attendanceService.getAttendanceForClass(classId, date);
        res.status(200).json({ data: records });
    } catch (error) {
        if (error.statusCode) {
            return res.status(error.statusCode).json({ error: error.message });
        }
        next(error);
    }
};

module.exports = {
    markAttendance,
    getStudentAttendance,
    getClassAttendance,
};
