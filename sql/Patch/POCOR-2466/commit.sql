-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-2466', NOW());

-- alert_rules table
RENAME TABLE `alerts` TO `alert_rules`;
ALTER TABLE `alert_rules` CHANGE `threshold` `threshold` VARCHAR(50) NOT NULL;
ALTER TABLE `alert_rules` CHANGE `status` `enabled` INT(1) NOT NULL DEFAULT '1';
ALTER TABLE `alert_rules` CHANGE `code` `feature` VARCHAR(50) NOT NULL;

-- alert_logs
ALTER TABLE `alert_logs` DROP `type`;

-- alert_roles table
RENAME TABLE `alert_roles` TO `alerts_roles`;
ALTER TABLE `alerts_roles` CHANGE `alert_id` `alert_rule_id` INT(11) NOT NULL COMMENT 'links to alert_rules.id';

ALTER TABLE `alerts_roles`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY(`alert_rule_id`, `security_role_id`),
    ADD KEY `alert_rule_id` (`alert_rule_id`),
    ADD KEY `security_role_id` (`security_role_id`);

-- security_user table
ALTER TABLE `security_users` ADD `email` VARCHAR(100) NULL AFTER `preferred_name`;

-- contact_options
DELETE FROM `contact_options` WHERE `name` = 'Email';

-- Security functions (permission)
UPDATE `security_functions` SET `_view` = 'Logs.index|Logs.view' WHERE `id` = 5031;
UPDATE `security_functions` SET `_delete` = 'Logs.remove' WHERE `id` = 5031;
UPDATE `security_functions` SET `order` = `order` + 2 WHERE `order` BETWEEN 5031 AND 5062;

DELETE FROM `security_functions` WHERE `id` = 5029;
DELETE FROM `security_functions` WHERE `id` = 5030;
DELETE FROM `security_role_functions` WHERE `security_function_id` = 5029;
DELETE FROM `security_role_functions` WHERE `security_function_id` = 5030;


INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('5062', 'Alerts', 'Alerts', 'Administration', 'Communications', '5000', 'Alerts.index|Alerts.view', 'Alerts.edit', NULL, NULL, 'Alerts.process', '5031', '1', NULL, NULL, '1', NOW()),
    ('5063', 'AlertRules', 'Alerts', 'Administration', 'Communications', '5000', 'AlertRules.index|AlertRules.view', 'AlertRules.edit', 'AlertRules.add', 'AlertRules.remove', NULL, '5032', '1', NULL, NULL, '1', NOW());

-- Table structure for table `alerts`
CREATE TABLE IF NOT EXISTS `alerts` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `process_name` varchar(50) NOT NULL,
    `process_id` int(11) DEFAULT NULL,
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `alerts` (`name`, `process_name`, `process_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('Attendance', 'AttendanceAlert', NULL, NULL, NULL, '1', NOW());

-- Drop table related to SMS
DROP TABLE sms_logs;
DROP TABLE sms_messages;
DROP TABLE sms_responses;




