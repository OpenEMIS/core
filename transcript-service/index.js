require('dotenv').config();
const express = require('express');
const transcriptRoutes = require('./src/routes/transcriptRoutes');
const logger = require('./src/services/loggingService');

const app = express();
const PORT = process.env.TRANSCRIPT_SERVICE_PORT || 3001;

app.use(express.json());

// Mount the transcript routes
app.use('/api', transcriptRoutes);

// Global error handler
app.use((err, req, res, next) => {
    logger.error(`${err.status || 500} - ${err.message} - ${req.originalUrl} - ${req.method} - ${req.ip}`);
    res.status(err.status || 500).json({
        error: {
            message: err.message || 'Internal Server Error',
        },
    });
});

app.listen(PORT, () => {
    console.log(`Transcript Service listening on port ${PORT}`);
    logger.info(`Transcript Service started on port ${PORT}`);
});

module.exports = app; // For testing purposes
