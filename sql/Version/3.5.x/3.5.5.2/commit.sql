-- POCOR-2447
-- db_patches

INSERT INTO db_patches (`issue`, `created`) VALUES ('POCOR-2447', NOW());

-- survey_rules
CREATE TABLE `survey_rules` (
  `id` CHAR(36) NOT NULL COMMENT '',
  `survey_form_id` INT NOT NULL COMMENT '',
  `survey_question_id` INT NOT NULL COMMENT '',
  `dependent_question_id` INT NOT NULL COMMENT '',
  `show_options` TEXT NOT NULL COMMENT '',
  `enabled` INT NOT NULL COMMENT '',
  `modified` DATETIME NULL COMMENT '',
  `modified_user_id` INT NULL COMMENT '',
  `created` DATETIME NOT NULL COMMENT '',
  `created_user_id` INT NOT NULL COMMENT '',
  PRIMARY KEY (`survey_form_id`, `survey_question_id`)
);


-- POCOR-2784
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2784', NOW());

-- backup the old table
ALTER TABLE `institution_subject_students`
RENAME TO  `z_2784_institution_subject_students`;

-- stagging table
CREATE TABLE IF NOT EXISTS `institution_subject_students_temp` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(1) NOT NULL,
  `total_mark` decimal(6,2) DEFAULT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_subject_id` int(11) NOT NULL,
  `institution_class_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `education_subject_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- re-insert data
INSERT INTO `institution_subject_students_temp`
SELECT
  `z_2784_institution_subject_students`.`id`,
  `z_2784_institution_subject_students`.`status`,
  `z_2784_institution_subject_students`.`total_mark`,
  `z_2784_institution_subject_students`.`student_id`,
  `z_2784_institution_subject_students`.`institution_subject_id`,
  `z_2784_institution_subject_students`.`institution_class_id`,
  `z_2784_institution_subject_students`.`institution_id`,
  `z_2784_institution_subject_students`.`academic_period_id`,
  `z_2784_institution_subject_students`.`education_subject_id`,
  `z_2784_institution_subject_students`.`modified_user_id`,
  `z_2784_institution_subject_students`.`modified`,
  `z_2784_institution_subject_students`.`created_user_id`,
  `z_2784_institution_subject_students`.`created`
FROM `z_2784_institution_subject_students`;

-- update to the right value
UPDATE `institution_subject_students_temp` SS
INNER JOIN `institution_subjects` S ON S.id = SS.`institution_subject_id`
SET SS.`education_subject_id` = S.`education_subject_id`;


-- real table
CREATE TABLE IF NOT EXISTS `institution_subject_students` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(1) NOT NULL,
  `total_mark` decimal(6,2) DEFAULT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_subject_id` int(11) NOT NULL,
  `institution_class_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `education_subject_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for table `institution_subject_students`
ALTER TABLE `institution_subject_students`
  ADD PRIMARY KEY (`student_id`,`institution_class_id`,`institution_id`,`academic_period_id`,`education_subject_id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `institution_subject_id` (`institution_subject_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

-- insert from stagging table
INSERT INTO `institution_subject_students`
SELECT * FROM `institution_subject_students_temp`
GROUP BY `student_id`, `institution_class_id`, `institution_id`, `academic_period_id`, `education_subject_id`;

DROP TABLE IF EXISTS `institution_subject_students_temp`;


-- POCOR-2723
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2723', NOW());

UPDATE security_functions
    SET
    `_view` = REPLACE (`_view`, 'Leaves', 'Leave'),
    `_edit` = REPLACE (`_edit`, 'Leaves', 'Leave'),
    `_add` = REPLACE (`_add`, 'Leaves', 'Leave'),
    `_delete` = REPLACE (`_delete`, 'Leaves', 'Leave'),
    `_execute` = REPLACE (`_execute`, 'Leaves', 'Leave')
WHERE id IN (3016, 7025);

UPDATE security_functions
    SET `name` = 'Leave'
        WHERE id = 7025;


-- POCOR-3003
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3003', NOW());

UPDATE security_group_users
JOIN institution_staff s ON s.security_group_user_id = security_group_users.id
JOIN institution_positions p ON p.id = s.institution_position_id
JOIN staff_position_titles t
    ON t.id = p.staff_position_title_id
    AND t.security_role_id <> security_group_users.security_role_id
SET security_group_users.security_role_id = t.security_role_id;


-- POCOR-2451
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2451', NOW());

-- custom_field_types
INSERT INTO `custom_field_types` (`code`, `name`, `value`, `description`, `format`, `is_mandatory`, `is_unique`, `visible`) VALUES
('REPEATER', 'Repeater', 'text_value', '', 'OpenEMIS_Institution', 0, 0, 1);

-- custom_modules
UPDATE `custom_modules` SET `supported_field_types` = CONCAT(`supported_field_types`, ',REPEATER') WHERE `model` = 'Institution.Institutions';

INSERT INTO `custom_modules` (`id`, `code`, `name`, `model`, `behavior`, `filter`, `supported_field_types`, `visible`, `parent_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(6, 'Institution > Repeater', 'Institution > Repeater > Survey', 'InstitutionRepeater.RepeaterSurveys', NULL, NULL, 'TEXT,NUMBER,DROPDOWN', 1, 0, NULL, NULL, 1, '0000-00-00 00:00:00');

-- institution_repeater_surveys
DROP TABLE IF EXISTS `institution_repeater_surveys`;
CREATE TABLE IF NOT EXISTS `institution_repeater_surveys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `institution_id` int(11) NOT NULL,
  `repeater_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `survey_form_id` int(11) NOT NULL,
  `parent_form_id` int(11) NOT NULL COMMENT 'links to institution_surveys.survey_form_id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `status_id` (`status_id`),
  INDEX `institution_id` (`institution_id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `survey_form_id` (`survey_form_id`),
  INDEX `parent_form_id` (`parent_form_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- institution_repeater_survey_answers
DROP TABLE IF EXISTS `institution_repeater_survey_answers`;
CREATE TABLE IF NOT EXISTS `institution_repeater_survey_answers` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `survey_question_id` int(11) NOT NULL,
  `institution_repeater_survey_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `survey_question_id` (`survey_question_id`),
  INDEX `institution_repeater_survey_id` (`institution_repeater_survey_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- institution_repeater_survey_table_cells
DROP TABLE IF EXISTS `institution_repeater_survey_table_cells`;
CREATE TABLE IF NOT EXISTS `institution_repeater_survey_table_cells` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `survey_question_id` int(11) NOT NULL,
  `survey_table_column_id` int(11) NOT NULL,
  `survey_table_row_id` int(11) NOT NULL,
  `institution_repeater_survey_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `survey_question_id` (`survey_question_id`),
  INDEX `survey_table_column_id` (`survey_table_column_id`),
  INDEX `survey_table_row_id` (`survey_table_row_id`),
  INDEX `institution_repeater_survey_id` (`institution_repeater_survey_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- POCOR-3006
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3006', NOW());

-- create new backup table
CREATE TABLE IF NOT EXISTS `z_3006_institution_positions` (
  `id` int(11) NOT NULL,
  `position_no` varchar(30) NOT NULL
)ENGINE=InnoDB COLLATE utf8mb4_unicode_ci;

-- Indexes for table `z_3006_institution_positions`
--
ALTER TABLE `z_3006_institution_positions`
  ADD PRIMARY KEY (`id`);

-- insert backup from main table

INSERT INTO `z_3006_institution_positions`
SELECT
  `institution_positions`.`id`,
  `institution_positions`.`position_no`
FROM `institution_positions`;

-- patch to remove blank space

UPDATE `institution_positions`
SET `position_no` = REPLACE(`position_no`, ' ', '');


-- POCOR-2992
-- db_patches
INSERT INTO db_patches (`issue`, `created`) VALUES ('POCOR-2992', NOW());

-- labels
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals' AND `field` = 'institution_id';
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals' AND `field` = 'previous_institution_id';
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'institution_id', 'Institution -> Staff Transfer Approvals', 'Requested Institution', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'previous_institution_id', 'Institution -> Staff Transfer Approvals', 'Current Institution', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'institution_position_id', 'Institution -> Staff Transfer Approvals', 'Requested Institution Position', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'start_date', 'Institution -> Staff Transfer Approvals', 'Requested Start Date', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'FTE', 'Institution -> Staff Transfer Approvals', 'Requested FTE', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'staff_type_id', 'Institution -> Staff Transfer Approvals', 'Requested Staff Type', 1, 1, NOW());


-- 3.5.5.2
UPDATE config_items SET value = '3.5.5.2' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
