const express = require('express');
const router = express.Router();
const { getTranscriptById, getLogs } = require('../controllers/transcriptController');

/**
 * This module defines all API routes for the /transcripts endpoint.
 * It maps the HTTP methods and URL paths to the corresponding controller functions.
 */

// --- Route Definitions ---

// Route to get all transcript generation logs.
// GET /transcripts/logs
// This route is defined *before* the '/:studentId' route to ensure that
// Express does not mistakenly interpret 'logs' as a student ID.
router.get('/logs', getLogs);

// Route to get a student's transcript by their ID.
// GET /transcripts/:studentId
// This route handles both JSON and PDF generation based on the 'format' query parameter.
router.get('/:studentId', getTranscriptById);

module.exports = router;
