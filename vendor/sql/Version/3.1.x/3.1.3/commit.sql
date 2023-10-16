-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1807');

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Forms.download' WHERE `id` = 5027;

-- institution_site_surveys
ALTER TABLE `institution_site_surveys` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '-1 -> Expired, 0 -> New, 1 -> Draft, 2 -> Completed';

-- security_rest_sessions
DROP TABLE IF EXISTS `security_rest_sessions`;
CREATE TABLE IF NOT EXISTS `security_rest_sessions` (
  `id` char(36) NOT NULL,
  `access_token` char(40) NOT NULL,
  `refresh_token` char(40) NOT NULL,
  `expiry_date` datetime NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime NOT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `security_rest_sessions`
  ADD PRIMARY KEY (`id`);

INSERT INTO `db_patches` VALUES ('PHPOE-1896');

ALTER TABLE `student_behaviours` CHANGE `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_behaviours` CHANGE `institution_site_id` `institution_id` INT(11) NOT NULL;
ALTER TABLE `student_behaviours` DROP INDEX security_user_id;
ALTER TABLE `student_behaviours` DROP INDEX institution_site_id;
ALTER TABLE `student_behaviours` ADD INDEX(`student_id`);
ALTER TABLE `student_behaviours` ADD INDEX(`institution_id`);

ALTER TABLE `staff_behaviours` CHANGE `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_behaviours` CHANGE `institution_site_id` `institution_id` INT(11) NOT NULL;
ALTER TABLE `staff_behaviours` DROP INDEX security_user_id;
ALTER TABLE `staff_behaviours` DROP INDEX institution_site_id;
ALTER TABLE `staff_behaviours` ADD INDEX(`staff_id`);
ALTER TABLE `staff_behaviours` ADD INDEX(`institution_id`);

UPDATE `security_functions` SET `_view` = 'Programmes.index|Programmes.view' WHERE `id` = 2011;
UPDATE `security_functions` SET `_view` = 'Sections.index|Sections.view' WHERE `id` = 2012;
UPDATE `security_functions` SET `_view` = 'Classes.index|Classes.view' WHERE `id` = 2013;
UPDATE `security_functions` SET `_view` = 'Absences.index|Absences.view' WHERE `id` = 2014;
UPDATE `security_functions` SET `_view` = 'Behaviours.index|Behaviours.view' WHERE `id` = 2015;

UPDATE `security_functions` SET `_view` = 'Positions.index|Positions.view' WHERE `id` = 3012;
UPDATE `security_functions` SET `_view` = 'Sections.index|Sections.view' WHERE `id` = 3013;
UPDATE `security_functions` SET `_view` = 'Classes.index|Classes.view' WHERE `id` = 3014;
UPDATE `security_functions` SET `_view` = 'Absences.index|Absences.view' WHERE `id` = 3015;
UPDATE `security_functions` SET `_view` = 'Behaviours.index|Behaviours.view' WHERE `id` = 3017;

UPDATE `config_items` SET `value` = '3.1.3' WHERE `code` = 'db_version';
