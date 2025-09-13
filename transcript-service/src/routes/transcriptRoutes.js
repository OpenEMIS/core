const express = require('express');
const router = express.Router();
const transcriptController = require('../controllers/transcriptController');

/**
 * This module defines all routes under the `/transcripts` path.
 * It imports the controller and maps specific routes to controller functions.
 * This separation of concerns (routing vs. business logic) is a key principle
 * of the Express framework.
 */

// Route to get a student transcript by their ID.
// GET /transcripts/:studentId
// The actual request handling is delegated to the `getTranscript` function
// in the `transcriptController`.
router.get('/:studentId', transcriptController.getTranscript);

// In the future, you could add more transcript-related routes here, e.g.:
// router.post('/', transcriptController.createTranscriptRequest);
// router.get('/batch/:batchId', transcriptController.getBatchTranscripts);

module.exports = router;
