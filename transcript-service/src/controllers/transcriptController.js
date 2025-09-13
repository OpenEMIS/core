const transcriptService = require('../services/transcriptService');
const logger = require('../services/loggingService');

/**
 * Handles the request to generate a student transcript.
 * @param {object} req - The Express request object.
 * @param {object} res - The Express response object.
 * @param {function} next - The Express next middleware function.
 */
const getTranscript = async (req, res, next) => {
    const { studentId } = req.params;
    // Determine format from query param, default to 'json'
    const format = req.query.format?.toLowerCase() || 'json';

    if (format !== 'json' && format !== 'pdf') {
        logger.warn(`Invalid format requested: ${format} for studentId: ${studentId}`);
        return res.status(400).json({ error: 'Invalid format requested. Use "json" or "pdf".' });
    }

    try {
        const result = await transcriptService.generateTranscript(studentId, format);

        if (format === 'pdf') {
            res.setHeader('Content-Type', 'application/pdf');
            res.setHeader('Content-Disposition', `attachment; filename=transcript-${studentId}.pdf`);
            res.send(result);
        } else {
            res.json(result);
        }
    } catch (error) {
        // Check if the error has a specific status code (like our 404 from the service)
        if (error.statusCode) {
            return res.status(error.statusCode).json({ error: error.message });
        }
        // For all other unexpected errors, pass to the global error handler
        next(error);
    }
};

/**
 * Handles the request to retrieve service logs.
 * @param {object} req - The Express request object.
 * @param {object} res - The Express response object.
 * @param {function} next - The Express next middleware function.
 */
const getLogs = async (req, res, next) => {
    try {
        const logs = await logger.getLogs();
        res.setHeader('Content-Type', 'text/plain');
        res.send(logs);
    } catch (error) {
        // Pass any errors to the global error handler
        next(error);
    }
};

module.exports = {
    getTranscript,
    getLogs,
};
