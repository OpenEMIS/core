const nodemailer = require('nodemailer');
const twilio = require('twilio');
const mysql = require('mysql2/promise');
const config = require('../../config/config');
const logger = require('./loggingService');
const coreDataService = require('./coreDataService');

// --- DB Pool for logging notifications ---
let pool;
const initializeDb = async () => {
    try {
        pool = mysql.createPool(config.db);
        await pool.getConnection();
        logger.info('Notification service successfully connected to its database for logging.');
    } catch (error) {
        logger.error(`Notification service failed to connect to its database: ${error.message}`);
        process.exit(1);
    }
};
initializeDb();

// --- Email and SMS Clients ---
const emailTransport = nodemailer.createTransport(config.email);
const smsClient = twilio(config.sms.accountSid, config.sms.authToken);

// --- Notification Logging ---
const logNotification = async (recipient, channel, content, status, errorMessage = null) => {
    const query = `
        INSERT INTO notification_logs (recipient, channel, content, status, error_message)
        VALUES (?, ?, ?, ?, ?);
    `;
    try {
        await pool.query(query, [recipient, channel, content, status, errorMessage]);
        logger.info(`Logged notification to ${recipient} via ${channel} with status ${status}.`);
    } catch (dbError) {
        // If logging to the DB fails, we log this error to the console/file to not lose it.
        logger.error(`CRITICAL: Failed to log notification to database. Recipient: ${recipient}, Status: ${status}. DB Error: ${dbError.message}`);
    }
};

// --- Sender Functions ---
const sendEmail = async (to, subject, body) => {
    const mailOptions = {
        from: config.email.from,
        to: to,
        subject: subject,
        html: body, // Assuming body is HTML. For plain text, use 'text' property.
    };
    try {
        await emailTransport.sendMail(mailOptions);
        await logNotification(to, 'EMAIL', body, 'SENT');
        return { success: true };
    } catch (error) {
        logger.error(`Failed to send email to ${to}: ${error.message}`);
        await logNotification(to, 'EMAIL', body, 'FAILED', error.message);
        return { success: false, error: 'Failed to send email.' }; // Don't expose detailed error
    }
};

const sendSms = async (to, message) => {
    try {
        await smsClient.messages.create({
            body: message,
            from: config.sms.fromNumber,
            to: to,
        });
        await logNotification(to, 'SMS', message, 'SENT');
        return { success: true };
    } catch (error) {
        logger.error(`Failed to send SMS to ${to}: ${error.message}`);
        await logNotification(to, 'SMS', message, 'FAILED', error.message);
        return { success: false, error: 'Failed to send SMS.' }; // Don't expose detailed error
    }
};

// --- Orchestrator Function ---
const processNotificationRequest = async (requestData) => {
    const { userId, channel = 'ALL', subject = 'Notification from OpenEMIS', message } = requestData;

    if (!userId || !message) {
        throw new Error('Request body must include at least userId and message.');
    }

    // 1. Get user contact info
    const contactInfo = await coreDataService.getUserContactInfo(userId);
    if (!contactInfo) {
        logger.warn(`No contact info found for user ${userId}. Cannot send notification.`);
        return { message: `No contact information found for user ID: ${userId}` };
    }

    const results = [];

    // 2. Send notifications based on channel
    if ((channel.toUpperCase() === 'EMAIL' || channel.toUpperCase() === 'ALL') && contactInfo.email) {
        const result = await sendEmail(contactInfo.email, subject, message);
        results.push({ channel: 'EMAIL', ...result });
    }

    if ((channel.toUpperCase() === 'SMS' || channel.toUpperCase() === 'ALL') && contactInfo.phone) {
        const result = await sendSms(contactInfo.phone, message);
        results.push({ channel: 'SMS', ...result });
    }

    if (results.length === 0) {
        return { message: 'No suitable channel found or specified for this user.' };
    }

    return results;
};

module.exports = {
    processNotificationRequest,
};
