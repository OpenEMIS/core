const mysql = require('mysql2/promise');
const config = require('../../config/config');
const logger = require('./loggingService');
const coreDataService = require('./coreDataService');

let pool;

const initializeDb = async () => {
    try {
        pool = mysql.createPool(config.db);
        await pool.getConnection();
        logger.info('Exam service successfully connected to its database.');
    } catch (error) {
        logger.error(`Exam service failed to connect to its database: ${error.message}`);
        process.exit(1);
    }
};

initializeDb();

// --- Exam Management ---

const createExam = async (examData) => {
    const { class_id, title, description, exam_date } = examData;
    const query = 'INSERT INTO exams (class_id, title, description, exam_date) VALUES (?, ?, ?, ?)';
    try {
        const [result] = await pool.query(query, [class_id, title, description, exam_date]);
        logger.info(`Created new exam with ID: ${result.insertId}`);
        return { id: result.insertId, ...examData };
    } catch (error) {
        logger.error(`Error creating exam: ${error.message}`);
        throw new Error('Failed to create exam.');
    }
};

const getExam = async (examId) => {
    const examQuery = 'SELECT * FROM exams WHERE id = ?';
    // Excludes correct_answer from the public-facing GET request
    const questionsQuery = 'SELECT id, question_text, question_type, options, points FROM exam_questions WHERE exam_id = ?';
    try {
        const [examRows] = await pool.query(examQuery, [examId]);
        if (examRows.length === 0) {
            return null; // Exam not found
        }
        const exam = examRows[0];
        const [questions] = await pool.query(questionsQuery, [examId]);
        exam.questions = questions;
        return exam;
    } catch (error) {
        logger.error(`Error retrieving exam ${examId}: ${error.message}`);
        throw new Error('Failed to retrieve exam.');
    }
};

// --- Question Management ---

const addQuestionToExam = async (examId, questionData) => {
    const { question_text, question_type, options, correct_answer, points } = questionData;
    const query = 'INSERT INTO exam_questions (exam_id, question_text, question_type, options, correct_answer, points) VALUES (?, ?, ?, ?, ?, ?)';
    try {
        const [result] = await pool.query(query, [examId, question_text, question_type, JSON.stringify(options), correct_answer, points]);
        logger.info(`Added question ${result.insertId} to exam ${examId}`);
        return { id: result.insertId, ...questionData };
    } catch (error) {
        logger.error(`Error adding question to exam ${examId}: ${error.message}`);
        throw new Error('Failed to add question.');
    }
};

// --- Student Interaction ---

const submitStudentAnswers = async (examId, studentId, answers) => {
    // Validate student exists in the core system
    const studentIsValid = await coreDataService.studentExists(studentId);
    if (!studentIsValid) {
        const error = new Error(`Student with ID ${studentId} does not exist.`);
        error.statusCode = 400;
        throw error;
    }

    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();
        // Using a single query with multiple value sets is more efficient
        const query = 'INSERT INTO student_answers (exam_id, question_id, student_id, answer_text) VALUES ?';
        const values = answers.map(ans => [examId, ans.question_id, studentId, ans.answer_text]);

        await connection.query(query, [values]);
        await connection.commit();

        logger.info(`Submitted ${answers.length} answers for student ${studentId} on exam ${examId}`);
        return { success: true, message: 'Answers submitted successfully.' };
    } catch (error) {
        await connection.rollback();
        logger.error(`Error submitting answers for student ${studentId} on exam ${examId}: ${error.message}`);
        throw new Error('Failed to submit answers.');
    } finally {
        connection.release();
    }
};

const gradeExamForStudent = async (examId, studentId) => {
    const connection = await pool.getConnection();
    try {
        // Get all questions with correct answers for the exam
        const questionsQuery = 'SELECT id, correct_answer, points FROM exam_questions WHERE exam_id = ?';
        const [questions] = await connection.query(questionsQuery, [examId]);

        // Get all of the student's answers for the exam
        const answersQuery = 'SELECT question_id, answer_text FROM student_answers WHERE exam_id = ? AND student_id = ?';
        const [answers] = await connection.query(answersQuery, [examId, studentId]);

        if (answers.length === 0) {
            throw new Error('No answers found for this student and exam to grade.');
        }

        const answerMap = new Map(answers.map(ans => [ans.question_id, ans.answer_text]));
        let score = 0;
        const totalPointsPossible = questions.reduce((total, q) => total + q.points, 0);

        questions.forEach(q => {
            const studentAnswer = answerMap.get(q.id);
            // Simple case-insensitive string comparison for grading.
            if (studentAnswer && q.correct_answer && studentAnswer.toLowerCase() === q.correct_answer.toLowerCase()) {
                score += q.points;
            }
        });

        // Save the result
        const resultQuery = `
            INSERT INTO exam_results (exam_id, student_id, score, total_points_possible)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE score = VALUES(score), total_points_possible = VALUES(total_points_possible);
        `;
        await connection.query(resultQuery, [examId, studentId, score, totalPointsPossible]);

        logger.info(`Graded exam ${examId} for student ${studentId}. Score: ${score}/${totalPointsPossible}`);
        return { exam_id: examId, student_id: studentId, score, total_points_possible: totalPointsPossible };

    } catch (error) {
        logger.error(`Failed to grade exam ${examId} for student ${studentId}: ${error.message}`);
        throw new Error('Failed to grade exam.');
    } finally {
        connection.release();
    }
};


module.exports = {
    createExam,
    getExam,
    addQuestionToExam,
    submitStudentAnswers,
    gradeExamForStudent,
};
