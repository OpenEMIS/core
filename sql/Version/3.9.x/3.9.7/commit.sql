-- POCOR-3731
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3731', NOW());

-- staff_behaviours
RENAME TABLE `staff_behaviours` TO `z_3731_staff_behaviours`;

DROP TABLE IF EXISTS `staff_behaviours`;
CREATE TABLE `staff_behaviours` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `description` text NOT NULL,
 `action` text NOT NULL,
 `date_of_behaviour` date NOT NULL,
 `time_of_behaviour` time DEFAULT NULL,
 `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
 `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
 `staff_behaviour_category_id` int(11) NOT NULL COMMENT 'links to staff_behaviour_categories.id',
 `behaviour_classification_id` int(11) NOT NULL COMMENT 'links to behaviour_classifications.id',
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`id`),
 KEY `staff_behaviour_category_id` (`staff_behaviour_category_id`),
 KEY `behaviour_classification_id` (`behaviour_classification_id`),
 KEY `staff_id` (`staff_id`),
 KEY `institution_id` (`institution_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='This table contains all behavioural records of staff';

INSERT INTO `staff_behaviours` (`id`, `description`, `action`, `date_of_behaviour`, `time_of_behaviour`, `staff_id`, `institution_id`, `staff_behaviour_category_id`, `behaviour_classification_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, CONCAT(`title`, ' - ',`description`), `action`, `date_of_behaviour`, `time_of_behaviour`, `staff_id`, `institution_id`, `staff_behaviour_category_id`, 0, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3731_staff_behaviours`;


-- POCOR-3726
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


-- POCOR-3111
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3111', NOW());

-- user_attachments_roles
DROP TABLE IF EXISTS `user_attachments_roles`;
CREATE TABLE IF NOT EXISTS `user_attachments_roles` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_attachment_id` int(11) NOT NULL COMMENT 'links to user_attachments.id',
  `security_role_id` int(11) NOT NULL COMMENT 'links to security_roles.id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of Attachments for specific Security Roles';

ALTER TABLE `user_attachments_roles`
  ADD PRIMARY KEY (`user_attachment_id`,`security_role_id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `id_index` (`id`),
  ADD KEY `user_attachment_id` (`user_attachment_id`),
  ADD KEY `security_role_id` (`security_role_id`);

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES
('6143285a-ac8d-11e6-8bda-525400b263eb', 'Attachments', 'security_roles', 'User -> Attachments', 'Shared', NULL, NULL, 1, NULL, NULL, 1, '2016-11-17 00:00:00');


-- POCOR-3570
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3570', NOW());

-- institutions table update the year_opened
CREATE TABLE `z_3570_institutions` LIKE `institutions`;
INSERT INTO `z_3570_institutions`
SELECT * FROM `institutions`;

UPDATE `institutions` SET `year_opened` = YEAR(`date_opened`);


-- POCOR-3886
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3886', NOW());

-- staff_employments
ALTER TABLE `staff_employments`
ADD `file_name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL  AFTER `comment`,
ADD `file_content` LONGBLOB NULL DEFAULT NULL  AFTER `file_name`;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('cdf0fca9-0a07-11e7-b9c5-525400b263eb', 'Employments', 'file_content', 'Staff > Employments', 'Attachment', NULL, NULL, 1, NULL, NULL, 1, '2017-03-16 00:00:00');

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Employments.download' WHERE `id` = 3019;
UPDATE `security_functions` SET `_execute` = 'StaffEmployments.download' WHERE `id` = 7020;


-- 3.9.7
UPDATE config_items SET value = '3.9.7' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
