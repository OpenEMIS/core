require('dotenv').config();
const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const analysisRoutes = require('./src/routes/analysisRoutes');
const logger = require('./src/services/loggingService');

const app = express();
const PORT = process.env.AI_SERVICE_PORT || 3005;

// Middleware
app.use(cors());
app.use(bodyParser.json());

// Mount the analysis routes
app.use('/api', analysisRoutes);

// A simple health check endpoint
app.get('/health', (req, res) => {
    res.status(200).json({ status: 'UP' });
});

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
    console.log(`AI Service listening on port ${PORT}`);
    logger.info(`AI Service started on port ${PORT}`);
});

module.exports = app; // For testing
