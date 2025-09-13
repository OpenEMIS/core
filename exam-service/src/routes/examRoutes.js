const express = require('express');
const router = express.Router();
const examController = require('../controllers/examController');

// --- Exam Routes ---

// Create a new exam
router.post('/exams', examController.createExam);

// Get details for a specific exam, including its questions
router.get('/exams/:examId', examController.getExam);


// --- Question Routes ---

// Add a new question to a specific exam
router.post('/exams/:examId/questions', examController.addQuestion);


// --- Student Interaction Routes ---

// Submit a student's answers for an exam
router.post('/exams/:examId/submissions', examController.submitAnswers);

// Trigger the grading process for a student's submission for an exam
router.post('/exams/:examId/grade', examController.gradeExam);


module.exports = router;
