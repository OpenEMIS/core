const examService = require('../services/examService');

// --- Exam Management ---

const createExam = async (req, res, next) => {
    try {
        // Basic validation
        const { class_id, title, exam_date } = req.body;
        if (!class_id || !title || !exam_date) {
            return res.status(400).json({ error: 'Missing required fields: class_id, title, exam_date.' });
        }
        const result = await examService.createExam(req.body);
        res.status(201).json({ message: 'Exam created successfully.', data: result });
    } catch (error) {
        next(error);
    }
};

const getExam = async (req, res, next) => {
    try {
        const { examId } = req.params;
        const exam = await examService.getExam(examId);
        if (!exam) {
            return res.status(404).json({ error: 'Exam not found.' });
        }
        res.status(200).json({ data: exam });
    } catch (error) {
        next(error);
    }
};

// --- Question Management ---

const addQuestion = async (req, res, next) => {
    try {
        const { examId } = req.params;
        const { question_text, question_type, points } = req.body;
        if (!question_text || !question_type || !points) {
            return res.status(400).json({ error: 'Missing required fields: question_text, question_type, points.' });
        }
        const result = await examService.addQuestionToExam(examId, req.body);
        res.status(201).json({ message: 'Question added successfully.', data: result });
    } catch (error) {
        next(error);
    }
};

// --- Student Interaction ---

const submitAnswers = async (req, res, next) => {
    try {
        const { examId } = req.params;
        const { student_id, answers } = req.body;
        if (!student_id || !answers || !Array.isArray(answers) || answers.length === 0) {
            return res.status(400).json({ error: 'Request must include student_id and a non-empty array of answers.' });
        }
        const result = await examService.submitStudentAnswers(examId, student_id, answers);
        res.status(200).json(result);
    } catch (error) {
        if (error.statusCode) {
            return res.status(error.statusCode).json({ error: error.message });
        }
        next(error);
    }
};

const gradeExam = async (req, res, next) => {
    try {
        const { examId } = req.params;
        const { student_id } = req.body;
        if (!student_id) {
            return res.status(400).json({ error: 'Request must include student_id.' });
        }
        const result = await examService.gradeExamForStudent(examId, student_id);
        res.status(200).json({ message: 'Exam graded successfully.', data: result });
    } catch (error) {
        next(error);
    }
};

module.exports = {
    createExam,
    getExam,
    addQuestion,
    submitAnswers,
    gradeExam,
};
