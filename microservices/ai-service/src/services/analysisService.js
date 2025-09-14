const dataConnector = require('./dataConnectorService');
const config = require('../../config/config');
const logger = require('./loggingService');

const { examScoreThreshold, attendanceAbsenceThreshold } = config.analysis;

/**
 * Fetches students with exam scores below a given threshold.
 */
const getStudentsFailingExams = async () => {
    const pool = dataConnector.getExamPool();
    if (!pool) throw new Error('Exam database pool is not initialized.');

    // This query calculates the percentage score and filters by the threshold.
    const query = `
        SELECT student_id, exam_id, (score / total_points_possible) * 100 AS percentage_score
        FROM exam_results
        WHERE (score / total_points_possible) * 100 < ?;
    `;
    try {
        const [rows] = await pool.query(query, [examScoreThreshold]);
        return rows;
    } catch (error) {
        logger.error(`Error fetching failing exam scores: ${error.message}`);
        throw new Error('Failed to get exam data.');
    }
};

/**
 * Fetches students with a number of absences above a given threshold.
 */
const getStudentsWithHighAbsences = async () => {
    const pool = dataConnector.getAttendancePool();
    if (!pool) throw new Error('Attendance database pool is not initialized.');

    // This query counts 'absent' statuses for each student.
    const query = `
        SELECT student_id, COUNT(*) as absence_count
        FROM attendance_records
        WHERE status = 'absent'
        GROUP BY student_id
        HAVING COUNT(*) >= ?;
    `;
    try {
        const [rows] = await pool.query(query, [attendanceAbsenceThreshold]);
        return rows;
    } catch (error) {
        logger.error(`Error fetching high absence counts: ${error.message}`);
        throw new Error('Failed to get attendance data.');
    }
};

/**
 * Fetches details for a list of student IDs from the core database.
 */
const getStudentDetails = async (studentIds) => {
    if (studentIds.length === 0) {
        return [];
    }
    const pool = dataConnector.getCorePool();
    if (!pool) throw new Error('Core database pool is not initialized.');

    const query = `SELECT id, first_name, last_name, email FROM students WHERE id IN (?)`;
    try {
        const [rows] = await pool.query(query, [studentIds]);
        return rows;
    } catch (error) {
        logger.error(`Error fetching student details: ${error.message}`);
        throw new Error('Failed to get student details from core db.');
    }
};


/**
 * Orchestrates the analysis to identify at-risk students.
 */
const identifyAtRiskStudents = async () => {
    logger.info('Starting at-risk student analysis...');
    try {
        const [failingStudents, absentStudents] = await Promise.all([
            getStudentsFailingExams(),
            getStudentsWithHighAbsences()
        ]);

        const atRiskData = new Map();

        failingStudents.forEach(s => {
            if (!atRiskData.has(s.student_id)) {
                atRiskData.set(s.student_id, { reasons: [] });
            }
            atRiskData.get(s.student_id).reasons.push(`Failed exam ${s.exam_id} with score ${s.percentage_score.toFixed(2)}%`);
        });

        absentStudents.forEach(s => {
            if (!atRiskData.has(s.student_id)) {
                atRiskData.set(s.student_id, { reasons: [] });
            }
            atRiskData.get(s.student_id).reasons.push(`Has ${s.absence_count} unexcused absences`);
        });

        const atRiskIds = Array.from(atRiskData.keys());
        if (atRiskIds.length === 0) {
            logger.info('Analysis complete. No at-risk students found.');
            return [];
        }

        const studentDetails = await getStudentDetails(atRiskIds);
        const studentDetailsMap = new Map(studentDetails.map(s => [s.id, s]));

        const results = atRiskIds.map(id => {
            const details = studentDetailsMap.get(id) || { id, first_name: 'Unknown', last_name: 'Student' };
            return {
                student_id: id,
                first_name: details.first_name,
                last_name: details.last_name,
                email: details.email,
                at_risk_reasons: atRiskData.get(id).reasons,
            };
        });

        logger.info(`Analysis complete. Found ${results.length} at-risk students.`);
        return results;

    } catch (error) {
        logger.error(`An error occurred during at-risk student analysis: ${error.message}`);
        throw new Error('Analysis could not be completed.');
    }
};

module.exports = {
    identifyAtRiskStudents,
};
