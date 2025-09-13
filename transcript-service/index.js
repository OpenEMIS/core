const express = require('express');
const config = require('./config/config');
const { logger } = require('./src/services/loggingService');
const transcriptRoutes = require('./src/routes/transcriptRoutes');

const app = express();

// --- Core Middleware ---

// Middleware to parse incoming JSON requests.
app.use(express.json());

// A simple middleware to log every incoming request.
// This is helpful for debugging and monitoring server activity.
app.use((req, res, next) => {
  logger.http(`Request received: ${req.method} ${req.originalUrl}`, {
    ip: req.ip,
    userAgent: req.get('User-Agent'),
  });
  next();
});


// --- Application Routes ---

// A root endpoint to provide a simple health check.
app.get('/', (req, res) => {
  res.status(200).send('Transcript Service is running and healthy.');
});

// All routes related to transcripts are handled by the transcriptRoutes module.
app.use('/transcripts', transcriptRoutes);


// --- Error Handling Middleware ---

// A catch-all for any routes that are not found.
// This should be placed after all other routes.
app.use((req, res, next) => {
  res.status(404).json({
    success: false,
    message: 'Endpoint not found. Please check the URL.',
  });
});

// A global error handler to catch any unexpected errors from other parts of the application.
// This ensures that the server doesn't crash and provides a generic, safe error response.
app.use((err, req, res, next) => {
  logger.error('An unhandled error occurred:', {
    error: err.message,
    stack: err.stack,
    url: req.originalUrl,
    method: req.method,
  });
  // Avoid sending stack trace to the client in production.
  res.status(500).json({
    success: false,
    message: 'An unexpected server error occurred.',
  });
});


// --- Server Startup ---
app.listen(config.port, () => {
  logger.info('------------------------------------');
  logger.info('Transcript Service Started');
  logger.info(`Server listening on: http://localhost:${config.port}`);
  logger.info(`Connection Mode: ${config.connectionMode}`);
  logger.info(`Log Level: ${config.logging.level}`);
  logger.info(`Output Directory: ${config.outputDir}`);
  logger.info('------------------------------------');
});
