const express = require('express');
const router = express.Router();
const analysisController = require('../controllers/analysisController');

// Route to get the list of at-risk students
router.get('/analysis/at-risk-students', analysisController.getAtRiskStudents);

module.exports = router;
