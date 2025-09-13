const coreDataService = require('./coreDataService');
const pdfService = require('./pdfService');
const logger = require('./loggingService');

/**
 * Fetches student data and generates a transcript.
 * This function orchestrates the process.
 * @param {string} studentId - The ID of the student.
 * @param {string} format - The desired format ('json' or 'pdf').
 * @returns {Promise<object|Buffer>} - The student data as JSON or the PDF buffer.
 */
const generateTranscript = async (studentId, format) => {
    logger.info(`Starting transcript generation for studentId: ${studentId} in format: ${format}`);

    try {
        // 1. Fetch student data
        const studentData = await coreDataService.getStudentData(studentId);

        // Handle case where student is not found
        if (!studentData) {
            logger.warn(`Transcript generation failed for studentId ${studentId}: Student not found.`);
            // Throw a specific error that the controller can identify
            const error = new Error('Student not found');
            error.statusCode = 404;
            throw error;
        }

        // In a real-world scenario, you would also fetch grades, attendance, etc.
        // For this example, we'll assume studentData contains all needed info.

        // 2. Generate the transcript in the requested format
        if (format === 'pdf') {
            logger.info(`Generating PDF for studentId: ${studentId}`);
            const pdfBuffer = await pdfService.createTranscriptPdf(studentData);
            return pdfBuffer;
        } else {
            logger.info(`Returning JSON data for studentId: ${studentId}`);
            // The studentData object from the service now contains the full transcript data
            return {
                message: "Transcript data retrieved successfully",
                transcript: studentData,
            };
        }
    } catch (error) {
        logger.error(`Failed to generate transcript for studentId ${studentId}: ${error.message}`);
        // Re-throw the error to be caught by the controller
        throw error;
    }
};

module.exports = {
    generateTranscript,
};
