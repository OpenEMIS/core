-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1346');

-- security_functions
UPDATE `security_functions` SET `_view` = 'Students.index|Students.view|StudentUser.view|StudentAccount.view|StudentSurveys.index|StudentSurveys.view', `_edit` = 'Students.edit|StudentUser.edit|StudentAccount.edit|StudentSurveys.edit' WHERE `id` = 1012;

-- New table - institution_student_surveys
DROP TABLE IF EXISTS `institution_student_surveys`;
CREATE TABLE IF NOT EXISTS `institution_student_surveys` (
  `id` int(11) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Draft, 2 -> Completed',
  `institution_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `academic_period_id` int(11) NOT NULL,
  `survey_form_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_student_surveys`
  ADD PRIMARY KEY (`id`), ADD KEY `institution_id` (`institution_id`), ADD KEY `student_id` (`student_id`), ADD KEY `survey_form_id` (`survey_form_id`), ADD KEY `academic_period_id` (`academic_period_id`);


ALTER TABLE `institution_student_surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - institution_student_survey_answers
DROP TABLE IF EXISTS `institution_student_survey_answers`;
CREATE TABLE IF NOT EXISTS `institution_student_survey_answers` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `survey_question_id` int(11) NOT NULL,
  `institution_student_survey_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_student_survey_answers`
  ADD PRIMARY KEY (`id`), ADD KEY `survey_question_id` (`survey_question_id`), ADD KEY `institution_student_survey_id` (`institution_student_survey_id`);

-- New table - institution_student_survey_table_cells
DROP TABLE IF EXISTS `institution_student_survey_table_cells`;
CREATE TABLE IF NOT EXISTS `institution_student_survey_table_cells` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `survey_question_id` int(11) NOT NULL,
  `survey_table_column_id` int(11) NOT NULL,
  `survey_table_row_id` int(11) NOT NULL,
  `institution_student_survey_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_student_survey_table_cells`
  ADD PRIMARY KEY (`id`), ADD KEY `survey_question_id` (`survey_question_id`), ADD KEY `survey_table_column_id` (`survey_table_column_id`), ADD KEY `survey_table_row_id` (`survey_table_row_id`), ADD KEY `institution_student_survey_id` (`institution_student_survey_id`);

-- custom_modules
ALTER TABLE `custom_modules` ADD `supported_field_types` VARCHAR(500) NULL DEFAULT NULL AFTER `filter`;

INSERT INTO `custom_modules` (`code`, `name`, `model`, `behavior`, `filter`, `supported_field_types`, `visible`, `parent_id`, `created_user_id`, `created`) VALUES
('Student List', 'Institution - Student List', 'Student.StudentSurveys', NULL, NULL, NULL, 1, 0, 1, '0000-00-00 00:00:00');

UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,STUDENT_LIST' WHERE `model` = 'Institution.Institutions';
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE' WHERE `model` = 'Student.Students';
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE' WHERE `model` = 'Staff.Staff';
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,DROPDOWN' WHERE `model` = 'Student.StudentSurveys';

-- custom_field_types
INSERT INTO `custom_field_types` (`code`, `name`, `value`, `description`, `format`, `is_mandatory`, `is_unique`, `visible`) VALUES
('STUDENT_LIST', 'Student List', 'text_value', '', 'OpenEMIS_Institution', 0, 0, 1);

-- New table - survey_question_params
DROP TABLE IF EXISTS `survey_question_params`;
CREATE TABLE IF NOT EXISTS `survey_question_params` (
  `id` char(36) NOT NULL,
  `param_key` varchar(100) NOT NULL,
  `param_value` varchar(100) DEFAULT NULL,
  `survey_question_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `survey_question_params`
  ADD PRIMARY KEY (`id`), ADD KEY `survey_question_id` (`survey_question_id`);

UPDATE security_functions SET _execute = 'Students.excel' WHERE security_functions.id = 1012;
UPDATE security_functions SET _execute = 'Staff.excel' WHERE security_functions.id = 1016;

-- select * from security_functions WHERE security_functions.name = 'Staff' and security_functions.controller = 'Institutions' and security_functions.module = 'Institutions' and security_functions.category = 'Staff'

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2016');

-- security_functions
UPDATE `security_functions` SET `_execute` = 'TransferRequests.add|TransferRequests.edit|Transfer.add' WHERE `id` = 1022;


INSERT INTO `db_patches` VALUES ('PHPOE-2036');

UPDATE config_items SET default_value = 1 WHERE code = 'institution_area_level_id';

INSERT INTO `db_patches` VALUES ('PHPOE-2063');

-- security_functions
UPDATE `security_functions` SET `_edit` = 'StudentAttendances.edit|StudentAttendances.indexEdit|StudentAbsences.edit' WHERE `id` = 1014;
UPDATE `security_functions` SET `_edit` = 'StaffAttendances.edit|StaffAttendances.indexEdit|StaffAbsences.edit' WHERE `id` = 1018;

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2072');

-- security_function
UPDATE `security_functions` SET `_view`='TransferRequests.index|TransferRequests.view', `_delete` = 'TransferRequests.remove' WHERE `id`='1022';

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'TransferRequests', 'created', 'Institutions -> Transfer Requests','Date of Application', 1, 1, NOW());

-- student_statuses
DELETE FROM `student_statuses` WHERE `code`='REJECTED';

INSERT INTO `db_patches` VALUES ('PHPOE-2088');

-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Assessments.view|Results.index' WHERE `id` = 1015;

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2019');

-- Institution Student dropout table
CREATE TABLE `institution_student_dropout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `effective_date` date NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Approve, 2 -> Reject',
  `institution_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `student_dropout_reason_id` int(11) NOT NULL,
  `comment` text,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- student_statuses
INSERT INTO `student_statuses` (`code`, `name`) 
VALUES ('PENDING_DROPOUT', 'Pending Dropout');

-- field_options
INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('Students', 'StudentDropoutReasons', 'Dropout Reasons', 'Student', NULL, 18, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- Dummy data for the student dropout reasons
INSERT INTO `field_option_values` (`name`, `order`, `visible`, `default`, `field_option_id`, `created_user_id`, `created`) 
VALUES ('Relocation', 1, 1, 1, (SELECT `field_options`.`id` FROM `field_options` WHERE `code` = 'StudentDropoutReasons'), 1, NOW());

-- Security function
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_execute`, `order`, `visible`, `created_user_id`, `created`) 
VALUES (1030, 'Dropout Request', 'Institutions', 'Institutions', 'Students', 1000,  'DropoutRequests.add|DropoutRequests.edit', 1030, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_execute`, `order`, `visible`, `created_user_id`, `created`) 
VALUES (1031, 'Student Dropout', 'Institutions', 'Institutions', 'Students', 1000, 'StudentDropout.index|StudentDropout.view', 'StudentDropout.edit|StudentDropout.view', 1031, 1, 1, NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StudentDropout', 'created', 'Institutions -> Student Dropout','Date of Application', 1, 1, NOW());

ALTER TABLE `security_group_users` ADD INDEX ( `security_group_id` ) ;
ALTER TABLE `security_group_users` ADD INDEX ( `security_user_id` ) ;
ALTER TABLE `security_group_users` ADD INDEX ( `security_role_id` ) ;

INSERT INTO `db_patches` VALUES ('PHPOE-1919');

UPDATE config_items SET name = 'Admission Age Plus' WHERE config_items.code = 'admission_age_plus';
UPDATE config_items SET label = 'Admission Age Plus' WHERE config_items.code = 'admission_age_plus';

INSERT INTO `db_patches` VALUES ('PHPOE-2117');

-- institution_site_programmes
ALTER TABLE `institution_site_programmes` 
RENAME TO  `z_2117_institution_site_programmes` ;

-- institution_site_grades
CREATE TABLE `z_2117_institution_site_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_site_programme_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=179 DEFAULT CHARSET=utf8;

INSERT INTO `z_2117_institution_site_grades` (`id`, `institution_site_programme_id`)
SELECT `id`, `institution_site_programme_id` FROM `institution_site_grades`;

ALTER TABLE `institution_site_grades` 
DROP COLUMN `institution_site_programme_id`,
DROP INDEX `institution_site_programme_id` ;

-- labels
DELETE FROM `labels` WHERE `module`='InstitutionSiteProgrammes';

UPDATE `config_items` SET `value` = '3.2.2' WHERE `code` = 'db_version';

