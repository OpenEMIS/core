-- POCOR-2449
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


-- POCOR-2450
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES('POCOR-2450', NOW());

INSERT INTO `custom_field_types` (`id`, `code`, `name`, `value`, `description`, `format`, `is_mandatory`, `is_unique`, `visible`) VALUES
(10, 'COORDINATES', 'Coordinates', 'text_value', '', 'OpenEMIS', 1, 0, 1);

-- backup tables
CREATE TABLE `z_2450_custom_modules` LIKE `custom_modules`;
INSERT INTO `z_2450_custom_modules` SELECT * FROM `custom_modules`;

UPDATE `custom_modules` SET `supported_field_types` = CONCAT(`supported_field_types`, ',COORDINATES') WHERE `model` = 'Institution.Institutions';
UPDATE `custom_modules` SET `supported_field_types` = CONCAT(`supported_field_types`, ',COORDINATES') WHERE `model` = 'Student.Students';
UPDATE `custom_modules` SET `supported_field_types` = CONCAT(`supported_field_types`, ',COORDINATES') WHERE `model` = 'Staff.Staff';
UPDATE `custom_modules` SET `supported_field_types` = CONCAT(`supported_field_types`, ',COORDINATES') WHERE `model` = 'Institution.InstitutionInfrastructures';


-- POCOR-2588
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2588', NOW());

-- CREATING EDITABLE COLUMBS
ALTER TABLE `academic_period_levels` ADD `editable` INT(1) NOT NULL DEFAULT TRUE AFTER `level`, ADD INDEX (`editable`);
UPDATE `academic_period_levels` SET `editable` = '0' WHERE `academic_period_levels`.`name` = 'Year';


-- BACKUP institution_students
CREATE TABLE z_2588_institution_students LIKE institution_students;
INSERT INTO z_2588_institution_students SELECT * FROM institution_students;

-- Table structure for table `z_2588_academic_period_parent`
CREATE TABLE IF NOT EXISTS `z_2588_academic_period_parent` (
  `period_name` varchar(50) NOT NULL,
  `period_id` int(11) NOT NULL,
  `parent_name` varchar(50) NOT NULL,
  `parent_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Indexes for table `z_2588_academic_period_parent`
ALTER TABLE `z_2588_academic_period_parent`
  ADD KEY `period_id` (`period_id`,`parent_id`);

ALTER TABLE `z_2588_academic_period_parent` CHANGE `parent_id` `parent_id` INT(11) NULL;
-- end z_2588_academic_period_parent


-- POPULATING z_2588_academic_period_parent WITH DATA OF YEAR PARENT
INSERT INTO z_2588_academic_period_parent (period_name, period_id, parent_name, parent_id)
SELECT t1.name, t1.id, '', (SELECT t2.id
       FROM academic_periods t2
       INNER JOIN academic_period_levels ON (t2.academic_period_level_id = academic_period_levels.id)
       WHERE t2.lft < t1.lft AND t2.rght > t1.rght AND academic_period_levels.name = 'Year'
       LIMIT 1)
AS year_parent_id FROM academic_periods t1;

-- REMOVING ENTRIES WITHOUT YEAR PARENT SO IT WILL NOT BE PART OF THE INNER JOIN
DELETE FROM z_2588_academic_period_parent WHERE parent_id IS NULL;

-- UPDATING ENTRIES WITH YEAR NAME FOR EASY VISUAL CHECKING
UPDATE z_2588_academic_period_parent
    INNER JOIN academic_periods ON (z_2588_academic_period_parent.parent_id = academic_periods.id)
    SET parent_name = academic_periods.name;

-- UPDATING ALL STUDENT RECORDS TO USE ACADEMIC PERIOD OF YEAR LEVEL IF AVAILABLE
UPDATE institution_students
    INNER JOIN z_2588_academic_period_parent ON (institution_students.academic_period_id = z_2588_academic_period_parent.period_id)
        SET institution_students.academic_period_id = z_2588_academic_period_parent.parent_id;


-- 3.5.4
UPDATE config_items SET value = '3.5.4' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
