-- POCOR-3714
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3714', NOW());

ALTER TABLE `system_errors` ADD `code` INT(5) NOT NULL AFTER `id`;
ALTER TABLE `system_errors` ADD `request_method` VARCHAR(10) NOT NULL AFTER `error_message`;
ALTER TABLE `system_errors` ADD `server_info` TEXT NOT NULL AFTER `stack_trace`;

CREATE TABLE `z_3714_config_product_lists` LIKE `config_product_lists`;

INSERT INTO `z_3714_config_product_lists`
SELECT * FROM `config_product_lists`;

Update `config_product_lists`
SET `auto_login_url` = CONCAT(TRIM(TRAILING '/' FROM `auto_login_url`), '/'), `auto_logout_url` = CONCAT(TRIM(TRAILING '/' FROM `auto_logout_url`), '/')
WHERE `deletable` = 0;

-- POCOR-3606
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3606', NOW());

-- table `user_nationalities`
RENAME TABLE `user_nationalities` TO `z_3606_user_nationalities`;

DROP TABLE IF EXISTS `user_nationalities`;
CREATE TABLE IF NOT EXISTS `user_nationalities` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `preferred` int(1) NOT NULL DEFAULT '0',
  `nationality_id` int(11) NOT NULL COMMENT 'links to nationalities.id',
  `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`nationality_id`, `security_user_id`),
  INDEX `nationality_id` (`nationality_id`),
  INDEX `security_user_id` (`security_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains nationality information of every user';

-- reinsert data
INSERT INTO `user_nationalities` (`id`, `nationality_id`, `comments`, `security_user_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`nationality_id`, ',', `security_user_id`), '256'), `nationality_id`, `comments`, `security_user_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3606_user_nationalities`
GROUP BY `security_user_id`, `nationality_id`;

-- update comment
UPDATE `user_nationalities` U
INNER JOIN `z_3606_user_nationalities` Z
  ON (Z.`nationality_id` = U.`nationality_id`
      AND Z.`security_user_id` = U.`security_user_id`
      AND (Z.`comments` IS NOT NULL AND Z.`comments` <> ''))
SET U.`comments` = Z.`comments`;

-- update preferred
UPDATE `user_nationalities` U
INNER JOIN (
  SELECT `security_user_id`, `nationality_id`
  FROM `z_3606_user_nationalities` Z1
  WHERE `created` = (
    SELECT MAX(`created`)
    FROM `z_3606_user_nationalities`
    WHERE Z1.`security_user_id` = `security_user_id`
  )
)AS Z
ON U.`security_user_id` = Z.`security_user_id`
AND U.`nationality_id` = Z.`nationality_id`
SET U.`preferred` = 1;

-- update security_user for its nationality_id, identity_type and identity_number
#reset everything first.
UPDATE `security_users` S
SET S.`identity_type_id` = NULL,
    S.`identity_number` = NULL,
    S.`nationality_id` = NULL;

# Fix incorrect datetime value
UPDATE `user_identities` SET `modified` = NULL WHERE `modified` = '0000-00-00 00:00:00';
UPDATE `user_identities` SET `created` = '1970-01-01 00:00:00' WHERE `created` = '0000-00-00 00:00:00';

#get nationality, identity and number based on the existing record.
UPDATE `security_users` S
INNER JOIN `user_nationalities` UN
ON (S.`id` = UN.`security_user_id`
    AND UN.`preferred` = 1
)
INNER JOIN `nationalities` N
ON N.`id` = UN.`nationality_id`
LEFT JOIN(
    SELECT `security_user_id`, `identity_type_id`, `number`
    FROM `user_identities` U1
    WHERE `created` = (
      SELECT MAX(`created`)
      FROM `user_identities`
      WHERE U1.`security_user_id` = `security_user_id`
      AND U1.`identity_type_id` = `identity_type_id`
    )
)AS UI
ON (UI.`identity_type_id` = N.`identity_type_id`
    AND UI.`security_user_id` = UN.`security_user_id`
)
SET S.`identity_type_id` = N.`identity_type_id`,
    S.`identity_number` = UI.`number`,
    S.`nationality_id` = N.`id`;


-- POCOR-2466
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-2466', NOW());

-- alert_rules table
RENAME TABLE `alerts` TO `alert_rules`;
ALTER TABLE `alert_rules` CHANGE `threshold` `threshold` VARCHAR(50) NOT NULL;
ALTER TABLE `alert_rules` CHANGE `status` `enabled` INT(1) NOT NULL DEFAULT '1';
ALTER TABLE `alert_rules` CHANGE `code` `feature` VARCHAR(50) NOT NULL;

-- alert_logs
DROP TABLE IF EXISTS `alert_logs`;
CREATE TABLE IF NOT EXISTS `alert_logs` (
  `id` int(11) NOT NULL,
  `method` varchar(20) NOT NULL,
  `destination` text NOT NULL,
  `status` varchar(20) NOT NULL COMMENT '-1 -> Failed, 0 -> Pending, 1 -> Success',
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `checksum` char(64) NOT NULL,
  `processed_date` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `alert_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `security_user_id` (`created_user_id`),
  ADD KEY `method` (`method`);

ALTER TABLE `alert_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- alert_roles table
RENAME TABLE `alert_roles` TO `alerts_roles`;
ALTER TABLE `alerts_roles` CHANGE `alert_id` `alert_rule_id` INT(11) NOT NULL COMMENT 'links to alert_rules.id';
ALTER TABLE `alerts_roles` CHANGE `id` `id` CHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `alerts_roles`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY(`alert_rule_id`, `security_role_id`),
    ADD KEY `alert_rule_id` (`alert_rule_id`);

-- security_user table
ALTER TABLE `security_users` ADD `email` VARCHAR(100) NULL AFTER `preferred_name`;

-- Security functions (permission)
UPDATE `security_functions` SET `_view` = 'Logs.index|Logs.view' WHERE `id` = 5031;
UPDATE `security_functions` SET `_delete` = 'Logs.remove' WHERE `id` = 5031;
UPDATE `security_functions` SET `order` = `order` + 2 WHERE `order` BETWEEN 5031 AND 5065;

DELETE FROM `security_functions` WHERE `id` = 5029;
DELETE FROM `security_functions` WHERE `id` = 5030;
DELETE FROM `security_role_functions` WHERE `security_function_id` = 5029;
DELETE FROM `security_role_functions` WHERE `security_function_id` = 5030;


INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('5064', 'Alerts', 'Alerts', 'Administration', 'Communications', '5000', 'Alerts.index|Alerts.view', NULL, NULL, NULL, 'Alerts.process', '5031', '1', NULL, NULL, '1', NOW()),
    ('5065', 'AlertRules', 'Alerts', 'Administration', 'Communications', '5000', 'AlertRules.index|AlertRules.view', 'AlertRules.edit', 'AlertRules.add', 'AlertRules.remove', NULL, '5032', '1', NULL, NULL, '1', NOW());

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


-- POCOR-3535
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3535', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('04865131-e90e-11e6-a68b-525400b263eb', 'SurveyQuestions', 'name', 'Survey -> Questions', 'Question', '1', '1', NOW());


-- POCOR-3537
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3537', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('ec92914b-e913-11e6-a68b-525400b263eb', 'RubricTemplates', 'name', 'Rubric -> Templates', 'Template', '1', '1', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('f3a106b5-e913-11e6-a68b-525400b263eb', 'RubricSections', 'name', 'Rubric -> Sections', 'Section', '1', '1', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('0823fd83-e914-11e6-a68b-525400b263eb', 'RubricCriterias', 'name', 'Rubric -> Criterias', 'Criteria', '1', '1', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('017c68d8-e914-11e6-a68b-525400b263eb', 'RubricTemplateOptions', 'name', 'Rubric -> Options', 'Option', '1', '1', NOW());


-- POCOR-3647
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3647', NOW());

-- institution_textbooks
ALTER TABLE `institution_textbooks`
ADD COLUMN `education_grade_id` INT(11) NULL AFTER `academic_period_id`,
ADD INDEX `education_grade_id` (`education_grade_id`);

UPDATE `institution_textbooks`
INNER JOIN `textbooks` ON `institution_textbooks`.`textbook_id` = `textbooks`.`id`
SET `institution_textbooks`.`education_grade_id` = `textbooks`.`education_grade_id`;

ALTER TABLE `institution_textbooks`
CHANGE COLUMN `education_grade_id` `education_grade_id` INT(11) NOT NULL ;


-- 3.9.2
UPDATE config_items SET value = '3.9.2' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
