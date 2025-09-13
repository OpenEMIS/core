-- =================================================================
-- Schema for the Notifications Service Database
-- =================================================================

-- This table stores a log of every notification attempt made by the service.
-- It is useful for auditing, debugging, and tracking communication history.

CREATE TABLE `notification_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recipient` VARCHAR(255) NOT NULL COMMENT 'The contact address, e.g., email or phone number.',
  `channel` ENUM('EMAIL', 'SMS') NOT NULL COMMENT 'The communication channel used.',
  `content` TEXT NOT NULL COMMENT 'The content of the message sent.',
  `status` ENUM('SENT', 'FAILED') NOT NULL,
  `error_message` TEXT DEFAULT NULL COMMENT 'Stores the error message if the notification failed to send.',
  `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_recipient` (`recipient`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Example of how to create the database and user:
-- CREATE DATABASE openemis_notifications;
-- CREATE USER 'notification_user'@'localhost' IDENTIFIED BY 'password';
-- GRANT ALL PRIVILEGES ON openemis_notifications.* TO 'notification_user'@'localhost';
-- FLUSH PRIVILEGES;
