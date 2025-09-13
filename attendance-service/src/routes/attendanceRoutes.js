const express = require('express');
const router = express.Router();
const attendanceController = require('../controllers/attendanceController');

// Route to mark attendance for a student
router.post('/attendance', attendanceController.markAttendance);

// Route to get all attendance records for a specific student
router.get('/attendance/student/:studentId', attendanceController.getStudentAttendance);

// Route to get all attendance records for a specific class on a given date
// The date should be passed as a query parameter, e.g., /api/attendance/class/123?date=2023-10-26
router.get('/attendance/class/:classId', attendanceController.getClassAttendance);

module.exports = router;
