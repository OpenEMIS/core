const express = require('express');
const config = require('./config/config');
const transcriptRoutes = require('./src/routes/transcriptRoutes');
const logger = require('./src/services/loggingService');

const app = express();

// Middleware to parse incoming JSON requests.
app.use(express.json());

// A simple root endpoint to provide a health check or welcome message.
app.get('/', (req, res) => {
  res.send('Transcript Service is running and ready to accept requests.');
});

// All routes related to transcripts are handled by the transcriptRoutes module.
// This keeps the main file clean and follows the single responsibility principle.
app.use('/transcripts', transcriptRoutes);

// A basic global error handler middleware.
// It catches any errors that occur in the route handlers and logs them.
app.use((err, req, res, next) => {
  logger.error(err.stack);
  // Send a generic error message to the client for security reasons.
  res.status(500).send('An unexpected error occurred. Please try again later.');
});

// Start the server and listen for incoming connections on the configured port.
app.listen(config.port, () => {
  logger.info(`Server started successfully on port ${config.port}`);
  logger.info(`Mode: Running in '${process.env.NODE_ENV || 'development'}' mode.`);
  logger.info(`Connecting to Core System via: ${config.connectionMode}`);
});
