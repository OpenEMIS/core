const coreDataService = require('../services/coreDataService');
const pdfService = require('../services/pdfService');
const logReaderService = require('../services/logReaderService');
const { logger, logTranscriptEvent } = require('../services/loggingService');

/**
 * Controller for handling transcript-related API requests.
 */

/**
 * Handles the request to get a student's transcript by their ID.
 * It validates the request, fetches data, and returns it in the specified format (JSON or PDF).
 * All outcomes (success, not found, error) are logged.
 * @param {object} req - The Express request object.
 * @param {object} res - The Express response object.
 */
const getTranscriptById = async (req, res) => {
  const { studentId } = req.params;
  const format = (req.query.format || 'json').toLowerCase();

  // 1. Validate the 'format' query parameter.
  if (format !== 'json' && format !== 'pdf') {
    logger.warn(`Invalid format requested: ${format}`);
    return res.status(400).json({
      success: false,
      message: "Invalid format specified. Please use 'json' or 'pdf'.",
    });
  }

  try {
    // 2. Fetch data from the core system.
    const studentData = await coreDataService.getStudentData(studentId);

    // 3. Handle case where student is not found.
    if (!studentData) {
      logTranscriptEvent({ studentId, format, status: 'NOT_FOUND' });
      return res.status(404).json({
        success: false,
        message: `Student with ID ${studentId} not found.`,
      });
    }

    // 4. Generate and return the response based on the format.
    if (format === 'pdf') {
      res.setHeader('Content-Type', 'application/pdf');
      res.setHeader('Content-Disposition', `attachment; filename="transcript-${studentId}.pdf"`);
      pdfService.generateTranscriptPdf(studentData, res);
    } else {
      res.status(200).json({ success: true, data: studentData });
    }

    // 5. Log the successful event.
    logTranscriptEvent({ studentId, format, status: 'SUCCESS' });

  } catch (error) {
    // 6. Handle any unexpected server errors.
    logger.error(`An unexpected error occurred while processing transcript for student ID ${studentId}:`, error);
    logTranscriptEvent({ studentId, format, status: 'ERROR', message: error.message });
    res.status(500).json({
      success: false,
      message: 'An internal server error occurred.',
    });
  }
};

/**
 * Handles the request to get all transcript generation logs.
 * It retrieves the logs from the log reader service and returns them as a JSON array.
 * @param {object} req - The Express request object.
 * @param {object} res - The Express response object.
 */
const getLogs = async (req, res) => {
  try {
    const logs = await logReaderService.getTranscriptLogs();
    res.status(200).json({ success: true, count: logs.length, logs });
  } catch (error) {
    logger.error('Failed to retrieve transcript logs:', error);
    res.status(500).json({
      success: false,
      message: 'An internal server error occurred while retrieving logs.',
    });
  }
};

module.exports = {
  getTranscriptById,
  getLogs,
};
