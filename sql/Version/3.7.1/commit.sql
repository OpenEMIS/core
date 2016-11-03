-- POCOR-2827
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2827', NOW());

-- config_items
INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`)
VALUES (1002, 'Type', 'external_data_source_type', 'External Data Source', 'Type', 'None', 'None', 1, 1, 'Dropdown', 'external_data_source_type', 1, NOW());

-- config_item_options
INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`) VALUES (100, 'external_data_source_type', 'None', 'None', 1, 1);
INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`) VALUES (101, 'external_data_source_type', 'OpenEMIS Identity', 'OpenEMIS Identity', 2, 1);

-- external_data_source_attributes
CREATE TABLE `external_data_source_attributes` (
  `id` char(36) NOT NULL,
  `external_data_source_type` varchar(50) NOT NULL,
  `attribute_field` varchar(50) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `value` text,
  `modified` datetime DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `created_user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- POCOR-3388
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3388', NOW());

-- institution_students
ALTER TABLE `institution_students`
ADD `previous_institution_student_id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NULL
AFTER `institution_id`,
ADD INDEX (`previous_institution_student_id`);

-- creating temp table
UPDATE `institution_students`
SET `start_date` = '1970-01-01'
WHERE `start_date` = '0000-00-00';

UPDATE `institution_students`
SET `end_date` = '1970-01-01'
WHERE `end_date` = '0000-00-00';

UPDATE `institution_students`
SET `created` = '1970-01-01'
WHERE `created` = '0000-00-00 00:00:00';

DROP TABLE IF EXISTS `institution_students_tmp`;
CREATE TABLE IF NOT EXISTS `institution_students_tmp` (
  `id` char(36) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `start_date` date NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains information of all students in every institution';

ALTER TABLE `institution_students_tmp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

INSERT INTO `institution_students_tmp`
SELECT `id`, `student_id`, `start_date`, `created`
FROM `institution_students`;

UPDATE `institution_students` `A`
SET `A`.`previous_institution_student_id` = (
        SELECT `id`
        FROM `institution_students_tmp` `B`
        WHERE `A`.`student_id` = `B`.`student_id`
        AND `A`.`start_date` > `B`.`start_date`
    ORDER BY `start_date` DESC
        LIMIT 1
);

DROP TABLE IF EXISTS `institution_students_tmp`;


-- POCOR-3444
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3444', NOW());

-- config_item_options

CREATE TABLE `z_3444_temp_language_mapping` (
  `lang_old` VARCHAR(3) NOT NULL COMMENT '',
  `lang_new` VARCHAR(3) NOT NULL COMMENT '',
  PRIMARY KEY (`lang_old`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;

INSERT INTO `z_3444_temp_language_mapping` (`lang_old`, `lang_new`) VALUES ('eng', 'en');
INSERT INTO `z_3444_temp_language_mapping` (`lang_old`, `lang_new`) VALUES ('chi', 'zh');
INSERT INTO `z_3444_temp_language_mapping` (`lang_old`, `lang_new`) VALUES ('ara', 'ar');
INSERT INTO `z_3444_temp_language_mapping` (`lang_old`, `lang_new`) VALUES ('fre', 'fr');
INSERT INTO `z_3444_temp_language_mapping` (`lang_old`, `lang_new`) VALUES ('spa', 'es');
INSERT INTO `z_3444_temp_language_mapping` (`lang_old`, `lang_new`) VALUES ('ru', 'ru');

UPDATE config_items
SET created = '1970-01-01 00:00:00'
WHERE created = '0000-00-00 00:00:00';

ALTER TABLE config_items CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE config_item_options CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

UPDATE `config_item_options`
INNER JOIN `z_3444_temp_language_mapping`
    ON `config_item_options`.`option_type` = 'language'
    AND `config_item_options`.`value` = `z_3444_temp_language_mapping`.`lang_old`
SET `config_item_options`.`value` = `z_3444_temp_language_mapping`.`lang_new`;

-- config_items
UPDATE `config_items`
INNER JOIN `z_3444_temp_language_mapping`
    ON `config_items`.`code` = 'language'
    AND `config_items`.`default_value` = `z_3444_temp_language_mapping`.`lang_old`
SET `config_items`.`default_value` = `z_3444_temp_language_mapping`.`lang_new`;

UPDATE `config_items`
INNER JOIN `z_3444_temp_language_mapping`
    ON `config_items`.`code` = 'language'
    AND `config_items`.`value` = `z_3444_temp_language_mapping`.`lang_old`
SET `config_items`.`value` = `z_3444_temp_language_mapping`.`lang_new`;

UPDATE `config_items`
SET name = 'Allow Users to change Language', label = 'Allow Users to change Language'
WHERE type = 'System' AND code = 'language_menu';

DROP TABLE `z_3444_temp_language_mapping`;

-- security_users
ALTER TABLE `security_users`
ADD COLUMN `preferred_language` CHAR(2) NULL COMMENT '' AFTER `photo_content`;

UPDATE `security_users`
SET `preferred_language` = (
    SELECT value FROM `config_items` WHERE `code` = 'language'
);


-- POCOR-3253
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3253', NOW());

-- workflow_models
RENAME TABLE `workflow_models` TO `z_3253_workflow_models`;

DROP TABLE IF EXISTS `workflow_models`;
CREATE TABLE IF NOT EXISTS `workflow_models` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `model` varchar(200) NOT NULL,
  `filter` varchar(200) DEFAULT NULL,
  `is_school_based` int(1) NOT NULL DEFAULT '0',
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of features that are workflow-enabled';

INSERT INTO `workflow_models` (`id`, `name`, `model`, `filter`, `is_school_based`, `created_user_id`, `created`)
SELECT `id`, `name`, `model`, `filter`, 0, `created_user_id`, NOW()
FROM `z_3253_workflow_models`;

UPDATE `workflow_models` SET `is_school_based` = 1
WHERE `model` IN ('Staff.Leaves', 'Institution.InstitutionSurveys', 'Institution.InstitutionPositions', 'Institution.StaffPositionProfiles');

UPDATE `workflow_models` SET `model` = 'Institution.StaffLeave' WHERE `model` = 'Staff.Leaves';

-- workflow_steps
RENAME TABLE `workflow_steps` TO `z_3253_workflow_steps`;

DROP TABLE IF EXISTS `workflow_steps`;
CREATE TABLE IF NOT EXISTS `workflow_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` int(1) NOT NULL DEFAULT '0' COMMENT '1 -> TO DO, 2 -> IN PROGRESS, 3 -> DONE',
  `is_editable` int(1) NOT NULL DEFAULT '0',
  `is_removable` int(1) NOT NULL DEFAULT '0',
  `is_system_defined` int(1) NOT NULL DEFAULT '0',
  `workflow_id` int(11) NOT NULL COMMENT 'links to workflows.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `workflow_id` (`workflow_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of steps used by all workflows';

INSERT INTO `workflow_steps` (`id`, `name`, `category`, `is_editable`, `is_removable`, `is_system_defined`, `workflow_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`,
  CASE `stage`
    WHEN 0 THEN 1
    WHEN 1 THEN 2
    WHEN 2 THEN 3
    WHEN NULL THEN 0
    ELSE 0
  END AS `category`, `is_editable`, `is_removable`,
  CASE `stage`
    WHEN 0 THEN 1
    WHEN 1 THEN 1
    WHEN 2 THEN 1
    ELSE 0
  END AS `is_system_defined`, `workflow_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3253_workflow_steps`;

-- workflow_actions
RENAME TABLE `workflow_actions` TO `z_3253_workflow_actions`;

DROP TABLE IF EXISTS `workflow_actions`;
CREATE TABLE IF NOT EXISTS `workflow_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `action` int(1) DEFAULT NULL COMMENT '0 -> Approve, 1 -> Reject',
  `visible` int(1) NOT NULL DEFAULT '1',
  `comment_required` int(1) NOT NULL DEFAULT '0',
  `allow_by_assignee` int(1) NOT NULL DEFAULT '0',
  `event_key` text,
  `workflow_step_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `next_workflow_step_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `allow_by_assignee` (`allow_by_assignee`),
  KEY `next_workflow_step_id` (`next_workflow_step_id`),
  KEY `workflow_step_id` (`workflow_step_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all actions used by different steps of any workflow';

INSERT INTO `workflow_actions` (`id`, `name`, `description`, `action`, `visible`, `next_workflow_step_id`, `event_key`, `comment_required`, `allow_by_assignee`, `workflow_step_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `WorkflowActions`.`id`, `WorkflowActions`.`name`, `WorkflowActions`.`description`, `WorkflowActions`.`action`, `WorkflowActions`.`visible`, `WorkflowActions`.`next_workflow_step_id`, `WorkflowActions`.`event_key`, `WorkflowActions`.`comment_required`,
  CASE `WorkflowSteps`.`category`
    WHEN 1 THEN 1
    ELSE 0
  END AS `allow_by_assignee`, `WorkflowActions`.`workflow_step_id`, `WorkflowActions`.`modified_user_id`, `WorkflowActions`.`modified`, `WorkflowActions`.`created_user_id`, `WorkflowActions`.`created`
FROM `z_3253_workflow_actions` AS `WorkflowActions`
INNER JOIN `workflow_steps` AS `WorkflowSteps`
ON `WorkflowSteps`.`id` = `WorkflowActions`.`workflow_step_id`;

-- workflow_records
RENAME TABLE `workflow_records` TO `z_3253_workflow_records`;

-- workflow_transitions
RENAME TABLE `workflow_transitions` TO `z_3253_workflow_transitions`;

DROP TABLE IF EXISTS `workflow_transitions`;
CREATE TABLE IF NOT EXISTS `workflow_transitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment` text,
  `prev_workflow_step_name` varchar(100) NOT NULL,
  `workflow_step_name` varchar(100) NOT NULL,
  `workflow_action_name` varchar(100) NOT NULL,
  `workflow_model_id` int(11) NOT NULL COMMENT 'links to workflow_models.id',
  `model_reference` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_model_id` (`workflow_model_id`),
  KEY `model_reference` (`model_reference`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains specific action executed by users to transit from one step to another';

INSERT INTO `workflow_transitions` (`id`, `comment`, `prev_workflow_step_name`, `workflow_step_name`, `workflow_action_name`, `workflow_model_id`, `model_reference`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `WorkflowTransitions`.`id`, `WorkflowTransitions`.`comment`, `WorkflowTransitions`.`prev_workflow_step_name`, `WorkflowTransitions`.`workflow_step_name`, `WorkflowTransitions`.`workflow_action_name`, `WorkflowRecords`.`workflow_model_id`, `WorkflowRecords`.`model_reference`, `WorkflowTransitions`.`modified_user_id`, `WorkflowTransitions`.`modified`, `WorkflowTransitions`.`created_user_id`, `WorkflowTransitions`.`created`
FROM `z_3253_workflow_transitions` `WorkflowTransitions`
INNER JOIN `z_3253_workflow_records` `WorkflowRecords`
ON `WorkflowRecords`.`id` = `WorkflowTransitions`.`workflow_record_id`;

-- institution_surveys
RENAME TABLE `institution_surveys` TO `z_3253_institution_surveys`;

DROP TABLE IF EXISTS `institution_surveys`;
CREATE TABLE IF NOT EXISTS `institution_surveys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `survey_form_id` int(11) NOT NULL COMMENT 'links to survey_forms.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status_id` (`status_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `survey_form_id` (`survey_form_id`),
  KEY `institution_id` (`institution_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of forms that all institutions need to complete and their current progress';

INSERT INTO `institution_surveys` (`id`, `status_id`, `academic_period_id`, `survey_form_id`, `institution_id`, `assignee_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `status_id`, `academic_period_id`, `survey_form_id`, `institution_id`, 0, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3253_institution_surveys`;

-- staff_leaves
RENAME TABLE `staff_leaves` TO `z_3253_staff_leaves`;

DROP TABLE IF EXISTS `institution_staff_leave`;
CREATE TABLE IF NOT EXISTS `institution_staff_leave` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `comments` text,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `staff_leave_type_id` int(11) NOT NULL COMMENT 'links to field_option_values.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `number_of_days` int(3) NOT NULL,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `staff_leave_type_id` (`staff_leave_type_id`),
  KEY `institution_id` (`institution_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `status_id` (`status_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all leave applications and their statuses of staff';

INSERT INTO `institution_staff_leave` (`id`, `date_from`, `date_to`, `comments`, `staff_id`, `staff_leave_type_id`, `institution_id`, `assignee_id`, `status_id`, `number_of_days`, `file_name`, `file_content`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `StaffLeaves`.`id`, `StaffLeaves`.`date_from`, `StaffLeaves`.`date_to`, `StaffLeaves`.`comments`, `StaffLeaves`.`staff_id`, `StaffLeaves`.`staff_leave_type_id`, `Staff`.`institution_id`, 0, `StaffLeaves`.`status_id`, `StaffLeaves`.`number_of_days`, `StaffLeaves`.`file_name`, `StaffLeaves`.`file_content`, `StaffLeaves`.`modified_user_id`, `StaffLeaves`.`modified`, `StaffLeaves`.`created_user_id`, `StaffLeaves`.`created`
FROM `z_3253_staff_leaves` AS `StaffLeaves`
INNER JOIN `institution_staff` AS `Staff`
ON `Staff`.`staff_id` = `StaffLeaves`.`staff_id`
INNER JOIN `staff_statuses` AS `StaffStatuses`
ON `StaffStatuses`.`id` = `Staff`.`staff_status_id`
WHERE `StaffStatuses`.`code` = 'ASSIGNED'
GROUP BY `Staff`.`staff_id`
ORDER BY `Staff`.`start_date` DESC;

-- institution_positions
RENAME TABLE `institution_positions` TO `z_3253_institution_positions`;

DROP TABLE IF EXISTS `institution_positions`;
CREATE TABLE IF NOT EXISTS `institution_positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `position_no` varchar(30) NOT NULL,
  `staff_position_title_id` int(11) NOT NULL COMMENT 'links to staff_position_titles.id',
  `staff_position_grade_id` int(11) NOT NULL COMMENT 'links to staff_position_grades.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `is_homeroom` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status_id` (`status_id`),
  KEY `staff_position_title_id` (`staff_position_title_id`),
  KEY `staff_position_grade_id` (`staff_position_grade_id`),
  KEY `institution_id` (`institution_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of positions offered by the institutions';

INSERT INTO `institution_positions` (`id`, `status_id`, `position_no`, `staff_position_title_id`, `staff_position_grade_id`, `institution_id`, `assignee_id`, `is_homeroom`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `status_id`, `position_no`, `staff_position_title_id`, `staff_position_grade_id`, `institution_id`, 0, `is_homeroom`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3253_institution_positions`;

-- institution_staff_position_profiles
RENAME TABLE `institution_staff_position_profiles` TO `z_3253_institution_staff_position_profiles`;

DROP TABLE IF EXISTS `institution_staff_position_profiles`;
CREATE TABLE IF NOT EXISTS `institution_staff_position_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_staff_id` int(11) NOT NULL COMMENT 'links to institution_staff.id',
  `staff_change_type_id` int(11) NOT NULL COMMENT 'links to staff_change_types.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `FTE` decimal(5,2) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `staff_type_id` int(5) NOT NULL COMMENT 'links to staff_types.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `institution_position_id` int(11) NOT NULL COMMENT 'links to institution_positions.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_staff_id` (`institution_staff_id`),
  KEY `staff_change_type_id` (`staff_change_type_id`),
  KEY `status_id` (`status_id`),
  KEY `staff_id` (`staff_id`),
  KEY `staff_type_id` (`staff_type_id`),
  KEY `institution_id` (`institution_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `institution_position_id` (`institution_position_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains change requests submitted for Staff profiles';

INSERT INTO `institution_staff_position_profiles` (`id`, `institution_staff_id`, `staff_change_type_id`, `status_id`, `FTE`, `start_date`, `end_date`, `staff_id`, `staff_type_id`, `institution_id`, `assignee_id`, `institution_position_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `institution_staff_id`, `staff_change_type_id`, `status_id`, `FTE`, `start_date`, `end_date`, `staff_id`, `staff_type_id`, `institution_id`, 0, `institution_position_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3253_institution_staff_position_profiles`;

-- training_courses
RENAME TABLE `training_courses` TO `z_3253_training_courses`;

DROP TABLE IF EXISTS `training_courses`;
CREATE TABLE IF NOT EXISTS `training_courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text,
  `objective` text,
  `credit_hours` int(3) NOT NULL,
  `duration` int(3) NOT NULL,
  `number_of_months` int(3) NOT NULL,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob,
  `training_field_of_study_id` int(11) NOT NULL COMMENT 'links to field_option_values.id',
  `training_course_type_id` int(11) NOT NULL COMMENT 'links to field_option_values.id',
  `training_mode_of_delivery_id` int(11) NOT NULL COMMENT 'links to field_option_values.id',
  `training_requirement_id` int(11) NOT NULL COMMENT 'links to field_option_values.id',
  `training_level_id` int(11) NOT NULL COMMENT 'links to field_option_values.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_field_of_study_id` (`training_field_of_study_id`),
  KEY `training_course_type_id` (`training_course_type_id`),
  KEY `training_mode_of_delivery_id` (`training_mode_of_delivery_id`),
  KEY `training_requirement_id` (`training_requirement_id`),
  KEY `training_level_id` (`training_level_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `status_id` (`status_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all training courses';

INSERT INTO `training_courses` (`id`, `code`, `name`, `description`, `objective`, `credit_hours`, `duration`, `number_of_months`, `file_name`, `file_content`, `training_field_of_study_id`, `training_course_type_id`, `training_mode_of_delivery_id`, `training_requirement_id`, `training_level_id`, `assignee_id`, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `code`, `name`, `description`, `objective`, `credit_hours`, `duration`, `number_of_months`, `file_name`, `file_content`, `training_field_of_study_id`, `training_course_type_id`, `training_mode_of_delivery_id`, `training_requirement_id`, `training_level_id`, 0, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3253_training_courses`;

-- training_sessions
RENAME TABLE `training_sessions` TO `z_3253_training_sessions`;

DROP TABLE IF EXISTS `training_sessions`;
CREATE TABLE IF NOT EXISTS `training_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `name` varchar(250) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `comment` text,
  `training_course_id` int(11) NOT NULL COMMENT 'links to training_courses.id',
  `training_provider_id` int(11) NOT NULL COMMENT 'links to field_option_values.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_course_id` (`training_course_id`),
  KEY `training_provider_id` (`training_provider_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `status_id` (`status_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all training sessions';

INSERT INTO `training_sessions` (`id`, `code`, `name`, `start_date`, `end_date`, `comment`, `training_course_id`, `training_provider_id`, `assignee_id`, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `code`, `name`, `start_date`, `end_date`, `comment`, `training_course_id`, `training_provider_id`, 0, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3253_training_sessions`;

-- training_session_results
RENAME TABLE `training_session_results` TO `z_3253_training_session_results`;

DROP TABLE IF EXISTS `training_session_results`;
CREATE TABLE IF NOT EXISTS `training_session_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_session_id` int(11) NOT NULL COMMENT 'links to training_sessions.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_session_id` (`training_session_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `status_id` (`status_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `training_session_results` (`id`, `training_session_id`, `assignee_id`, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `training_session_id`, 0, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3253_training_session_results`;

-- staff_training_needs
RENAME TABLE `staff_training_needs` TO `z_3253_staff_training_needs`;

DROP TABLE IF EXISTS `staff_training_needs`;
CREATE TABLE IF NOT EXISTS `staff_training_needs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comments` text,
  `course_code` varchar(60) DEFAULT NULL,
  `course_name` varchar(250) DEFAULT NULL,
  `course_description` text,
  `course_id` int(11) NOT NULL COMMENT 'links to training_courses.id',
  `training_need_category_id` int(11) NOT NULL COMMENT 'links to field_option_values.id',
  `training_requirement_id` int(11) NOT NULL COMMENT 'links to field_option_values.id',
  `training_priority_id` int(11) NOT NULL COMMENT 'links to field_option_values.id',
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `training_need_category_id` (`training_need_category_id`),
  KEY `training_requirement_id` (`training_requirement_id`),
  KEY `training_priority_id` (`training_priority_id`),
  KEY `staff_id` (`staff_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `status_id` (`status_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `staff_training_needs` (`id`, `comments`, `course_code`, `course_name`, `course_description`, `course_id`, `training_need_category_id`, `training_requirement_id`, `training_priority_id`, `staff_id`, `assignee_id`, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `comments`, `course_code`, `course_name`, `course_description`, `course_id`, `training_need_category_id`, `training_requirement_id`, `training_priority_id`, `staff_id`, 0, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3253_staff_training_needs`;

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 5049;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (5049, 'Actions', 'Workflows', 'Administration', 'Workflows', 5000, 'Actions.index|Actions.view', 'Actions.edit', 'Actions.add', 'Actions.remove', NULL, 5039, 1, 1, NOW());

UPDATE `security_functions` SET `order` = 5049 WHERE `id` = 5038;

UPDATE `security_functions` SET `controller` = 'Institutions', `_view` = 'StaffLeave.index|StaffLeave.view', `_edit` = 'StaffLeave.edit', `_add` = 'StaffLeave.add', `_delete` = 'StaffLeave.remove', `_execute` = 'StaffLeave.download' WHERE `id` = 3016;


-- 3.7.1
UPDATE config_items SET value = '3.7.1' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
