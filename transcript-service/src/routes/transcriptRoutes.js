const express = require('express');
const router = express.Router();
const transcriptController = require('../controllers/transcriptController');

/**
 * @swagger
 * /transcripts/{studentId}:
 *   get:
 *     summary: Retrieve a student's transcript
 *     description: Fetches a student's transcript, available in either JSON or PDF format.
 *     parameters:
 *       - in: path
 *         name: studentId
 *         required: true
 *         description: Numeric ID of the student to retrieve.
 *         schema:
 *           type: string
 *       - in: query
 *         name: format
 *         required: false
 *         description: The format of the transcript (json or pdf). Defaults to json.
 *         schema:
 *           type: string
 *           enum: [json, pdf]
 *     responses:
 *       200:
 *         description: A student transcript.
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *           application/pdf:
 *             schema:
 *               type: string
 *               format: binary
 *       404:
 *         description: Student not found.
 */
router.get('/transcripts/:studentId', transcriptController.getTranscript);

/**
 * @swagger
 * /transcripts/logs:
 *   get:
 *     summary: Retrieve service logs
 *     description: Fetches the main service log file as plain text.
 *     responses:
 *       200:
 *         description: The service log file.
 *         content:
 *           text/plain:
 *             schema:
 *               type: string
 *       500:
 *         description: Server error when trying to read the log file.
 */
router.get('/transcripts/logs', transcriptController.getLogs);

module.exports = router;
