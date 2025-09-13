const notificationService = require('../services/notificationService');

/**
 * Handles the incoming request to send a notification.
 */
const sendNotification = async (req, res, next) => {
    try {
        const { userId, channel, subject, message } = req.body;

        // Basic validation
        if (!userId || !message) {
            return res.status(400).json({ error: 'Request body must include at least userId and message.' });
        }

        const result = await notificationService.processNotificationRequest(req.body);
        res.status(200).json({ message: 'Notification request processed.', data: result });
    } catch (error) {
        // Forward to global error handler
        next(error);
    }
};

module.exports = {
    sendNotification,
};
