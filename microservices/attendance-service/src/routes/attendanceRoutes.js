const express = require('express');
const router = express.Router();
const attendanceController = require('../controllers/attendanceController');

// Student Attendance
router.get('/students/:studentId/attendance', attendanceController.getStudentAttendance);
router.get('/classes/:classId/attendance', attendanceController.getClassAttendance);
router.post('/classes/:classId/attendance', attendanceController.postClassAttendance);

// Staff Attendance
router.get('/staff/:staffId/attendance', attendanceController.getStaffAttendance);
router.post('/staff/:staffId/attendance', attendanceController.postStaffAttendance);

// Supporting Resources
router.get('/absence-types', attendanceController.getAbsenceTypes);
router.get('/absence-reasons', attendanceController.getAbsenceReasons);

module.exports = router;
