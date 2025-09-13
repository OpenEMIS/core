const express = require('express');
const router = express.Router();
const notificationController = require('../controllers/notificationController');

// Route to send a notification.
// The request body should contain details like userId, channel, subject, and message.
router.post('/send', notificationController.sendNotification);

module.exports = router;
