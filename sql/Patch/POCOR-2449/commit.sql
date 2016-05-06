-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2449', NOW());

-- custom_field_types
INSERT INTO `custom_field_types` (`id`, `code`, `name`, `value`, `description`, `format`, `is_mandatory`, `is_unique`, `visible`) VALUES
(11, 'FILE', 'File', 'file', '', 'OpenEMIS', 0, 0, 1);

-- custom_modules
UPDATE `custom_modules` SET `supported_field_types` = CONCAT(`supported_field_types`, ',FILE') WHERE `model` = 'Institution.Institutions';
UPDATE `custom_modules` SET `supported_field_types` = CONCAT(`supported_field_types`, ',FILE') WHERE `model` = 'Student.Students';
UPDATE `custom_modules` SET `supported_field_types` = CONCAT(`supported_field_types`, ',FILE') WHERE `model` = 'Staff.Staff';
UPDATE `custom_modules` SET `supported_field_types` = CONCAT(`supported_field_types`, ',FILE') WHERE `model` = 'Institution.InstitutionInfrastructures';

-- custom_field_values
RENAME TABLE `custom_field_values` TO `z_2449_custom_field_values`;

DROP TABLE IF EXISTS `custom_field_values`;
CREATE TABLE IF NOT EXISTS `custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob DEFAULT NULL,
  `custom_field_id` int(11) NOT NULL,
  `custom_record_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `custom_field_id` (`custom_field_id`),
  INDEX `custom_record_id` (`custom_record_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `custom_field_values` (`id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, `file`, `custom_field_id`, `custom_record_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, NULL, `custom_field_id`, `custom_record_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_2449_custom_field_values`;

-- institution_custom_field_values
RENAME TABLE `institution_custom_field_values` TO `z_2449_institution_custom_field_values`;

DROP TABLE IF EXISTS `institution_custom_field_values`;
CREATE TABLE IF NOT EXISTS `institution_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob DEFAULT NULL,
  `institution_custom_field_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `institution_custom_field_id` (`institution_custom_field_id`),
  INDEX `institution_id` (`institution_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `institution_custom_field_values` (`id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, `file`, `institution_custom_field_id`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, NULL, `institution_custom_field_id`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_2449_institution_custom_field_values`;

-- student_custom_field_values
RENAME TABLE `student_custom_field_values` TO `z_2449_student_custom_field_values`;

DROP TABLE IF EXISTS `student_custom_field_values`;
CREATE TABLE IF NOT EXISTS `student_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob DEFAULT NULL,
  `student_custom_field_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `student_custom_field_id` (`student_custom_field_id`),
  INDEX `student_id` (`student_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `student_custom_field_values` (`id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, `file`, `student_custom_field_id`, `student_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, NULL, `student_custom_field_id`, `student_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_2449_student_custom_field_values`;

-- staff_custom_field_values
RENAME TABLE `staff_custom_field_values` TO `z_2449_staff_custom_field_values`;

DROP TABLE IF EXISTS `staff_custom_field_values`;
CREATE TABLE IF NOT EXISTS `staff_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob DEFAULT NULL,
  `staff_custom_field_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `staff_custom_field_id` (`staff_custom_field_id`),
  INDEX `staff_id` (`staff_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `staff_custom_field_values` (`id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, `file`, `staff_custom_field_id`, `staff_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, NULL, `staff_custom_field_id`, `staff_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_2449_staff_custom_field_values`;

-- infrastructure_custom_field_values
RENAME TABLE `infrastructure_custom_field_values` TO `z_2449_infrastructure_custom_field_values`;

DROP TABLE IF EXISTS `infrastructure_custom_field_values`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob DEFAULT NULL,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `institution_infrastructure_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `infrastructure_custom_field_id` (`infrastructure_custom_field_id`),
  INDEX `institution_infrastructure_id` (`institution_infrastructure_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `infrastructure_custom_field_values` (`id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_infrastructure_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, NULL, `infrastructure_custom_field_id`, `institution_infrastructure_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_2449_infrastructure_custom_field_values`;

-- institution_survey_answers
RENAME TABLE `institution_survey_answers` TO `z_2449_institution_survey_answers`;

DROP TABLE IF EXISTS `institution_survey_answers`;
CREATE TABLE IF NOT EXISTS `institution_survey_answers` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob DEFAULT NULL,
  `survey_question_id` int(11) NOT NULL,
  `institution_survey_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `survey_question_id` (`survey_question_id`),
  INDEX `institution_survey_id` (`institution_survey_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `institution_survey_answers` (`id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, `file`, `survey_question_id`, `institution_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, NULL, `survey_question_id`, `institution_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_2449_institution_survey_answers`;

-- institution_student_survey_answers
RENAME TABLE `institution_student_survey_answers` TO `z_2449_institution_student_survey_answers`;

DROP TABLE IF EXISTS `institution_student_survey_answers`;
CREATE TABLE IF NOT EXISTS `institution_student_survey_answers` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob DEFAULT NULL,
  `survey_question_id` int(11) NOT NULL,
  `institution_student_survey_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `survey_question_id` (`survey_question_id`),
  INDEX `institution_student_survey_id` (`institution_student_survey_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `institution_student_survey_answers` (`id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, `file`, `survey_question_id`, `institution_student_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, NULL, `survey_question_id`, `institution_student_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_2449_institution_student_survey_answers`;

-- user_activities
RENAME TABLE `user_activities` TO `z_2449_user_activities`;

DROP TABLE IF EXISTS `user_activities`;
CREATE TABLE IF NOT EXISTS `user_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(200) NOT NULL,
  `model_reference` int(11) NOT NULL,
  `field` varchar(200) NOT NULL,
  `field_type` varchar(128) NOT NULL,
  `old_value` varchar(255) DEFAULT NULL,
  `new_value` varchar(255) DEFAULT NULL,
  `operation` varchar(10) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `model_reference` (`model_reference`),
  INDEX `security_user_id` (`security_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user_activities` SELECT * FROM `z_2449_user_activities`;

-- institution_activities
RENAME TABLE `institution_activities` TO `z_2449_institution_activities`;

DROP TABLE IF EXISTS `institution_activities`;
CREATE TABLE IF NOT EXISTS `institution_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(200) NOT NULL,
  `model_reference` int(11) NOT NULL,
  `field` varchar(200) NOT NULL,
  `field_type` varchar(128) NOT NULL,
  `old_value` varchar(255) DEFAULT NULL,
  `new_value` varchar(255) DEFAULT NULL,
  `operation` varchar(10) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `model_reference` (`model_reference`),
  INDEX `institution_id` (`institution_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `institution_activities` SELECT * FROM `z_2449_institution_activities`;
