-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3726', NOW());

-- alert_logs
RENAME TABLE `alert_logs` TO `z_3726_alert_logs`;

DROP TABLE IF EXISTS `alert_logs`;
CREATE TABLE IF NOT EXISTS `alert_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feature` varchar(100) NOT NULL,
  `method` varchar(20) NOT NULL,
  `destination` text NOT NULL,
  `status` varchar(20) NOT NULL COMMENT '-1 -> Failed, 0 -> Pending, 1 -> Success',
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `checksum` char(64) NOT NULL,
  `processed_date` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `security_user_id` (`created_user_id`),
  KEY `method` (`method`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of alert logs for a given feature';

INSERT INTO `alert_logs` (`id`, `feature`, `method`, `destination`, `status`, `subject`, `message`, `checksum`, `processed_date`, `created_user_id`, `created`)
SELECT `id`, 'Attendance', `method`, `destination`, `status`, `subject`, `message`, `checksum`, `processed_date`, `created_user_id`, `created` FROM `z_3726_alert_logs`;

-- workflows
ALTER TABLE `workflows` ADD `message` TEXT DEFAULT NULL AFTER `name`;
