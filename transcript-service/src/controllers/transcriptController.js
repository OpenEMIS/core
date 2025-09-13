const coreDataService = require('../services/coreDataService');
const pdfService = require('../services/pdfService');
const logger = require('../services/loggingService');

/**
 * Handles the request to get a student's transcript.
 *
 * This function orchestrates the process:
 * 1. Extracts student ID and desired format from the request.
 * 2. Calls the `coreDataService` to fetch the student's academic records.
 * 3. If the student is not found, it returns a 404 error.
 * 4. Based on the 'format' query parameter ('json' or 'pdf'), it either:
 *    a) Sends the data back as a JSON response.
 *    b) Calls the `pdfService` to generate and stream a PDF document.
 * 5. Logs the outcome of the request.
 *
 * @param {object} req - The Express request object, containing params and query.
 * @param {object} res - The Express response object, used to send the response.
 */
const getTranscript = async (req, res) => {
  const { studentId } = req.params;
  // The desired output format can be specified via a query param, e.g., /transcripts/123?format=pdf
  const { format = 'json' } = req.query;

  logger.info(`Processing transcript request for student ID: ${studentId} [format: ${format}]`);

  try {
    // 1. Fetch the student data from the core system.
    const studentData = await coreDataService.getStudentData(studentId);

    // 2. Handle the case where the student is not found.
    if (!studentData) {
      logger.warn(`No data found for student ID: ${studentId}.`);
      return res.status(404).json({
        success: false,
        message: `Student with ID ${studentId} not found.`,
      });
    }

    // 3. Generate the response based on the requested format.
    if (format.toLowerCase() === 'pdf') {
      // For PDF, set appropriate headers for file download.
      res.setHeader('Content-Type', 'application/pdf');
      res.setHeader('Content-Disposition', `attachment; filename="transcript-${studentId}.pdf"`);

      // The pdfService will stream the PDF directly to the HTTP response stream.
      pdfService.generateTranscriptPdf(studentData, res);

      logger.info(`Successfully streamed PDF transcript for student ID: ${studentId}.`);
    } else {
      // For JSON, send the data with a 200 OK status.
      res.status(200).json({
        success: true,
        data: studentData,
      });
      logger.info(`Successfully sent JSON transcript for student ID: ${studentId}.`);
    }
  } catch (error) {
    logger.error(`Error processing transcript for student ID ${studentId}:`, error);
    // Send a generic error response to the client.
    res.status(500).json({
      success: false,
      message: 'An internal server error occurred while generating the transcript.',
    });
  }
};

module.exports = {
  getTranscript,
};
