-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3253', NOW());

-- workflow_models
RENAME TABLE `workflow_models` TO `z_3253_workflow_models`;
DROP TABLE IF EXISTS `workflow_models`;
CREATE TABLE IF NOT EXISTS `workflow_models` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `model` varchar(200) NOT NULL,
  `filter` varchar(200) DEFAULT NULL,
  `is_school_based` int(1) NOT NULL DEFAULT '0',
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of features that are workflow-enabled';

INSERT INTO `workflow_models` (`id`, `name`, `model`, `filter`, `is_school_based`, `created_user_id`, `created`)
SELECT `id`, `name`, `model`, `filter`, 0, `created_user_id`, `created`
FROM `z_3253_workflow_models`;

UPDATE `workflow_models` SET `is_school_based` = 1
WHERE `model` IN ('Staff.Leaves', 'Institution.InstitutionSurveys', 'Institution.InstitutionPositions', 'Institution.StaffPositionProfiles');

-- institution_surveys
RENAME TABLE `institution_surveys` TO `z_3253_institution_surveys`;

DROP TABLE IF EXISTS `institution_surveys`;
CREATE TABLE IF NOT EXISTS `institution_surveys` (
  `id` int(11) NOT NULL,
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

DROP TABLE IF EXISTS `staff_leaves`;
CREATE TABLE IF NOT EXISTS `staff_leaves` (
  `id` int(11) NOT NULL,
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
  KEY `status_id` (`status_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all leave applications and their statuses of staff';

INSERT INTO `staff_leaves` (`id`, `date_from`, `date_to`, `comments`, `staff_id`, `staff_leave_type_id`, `institution_id`, `assignee_id`, `status_id`, `number_of_days`, `file_name`, `file_content`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `date_from`, `date_to`, `comments`, `staff_id`, `staff_leave_type_id`, 0, 0, `status_id`, `number_of_days`, `file_name`, `file_content`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3253_staff_leaves`;

-- institution_positions
RENAME TABLE `institution_positions` TO `z_3253_institution_positions`;

DROP TABLE IF EXISTS `institution_positions`;
CREATE TABLE IF NOT EXISTS `institution_positions` (
  `id` int(11) NOT NULL,
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
  `id` int(11) NOT NULL,
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

INSERT INTO `institution_staff_position_profiles` (`id`, `institution_staff_id`, `staff_change_type_id`, `status_id`, `staff_id`, `staff_type_id`, `institution_id`, `assignee_id`, `institution_position_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `institution_staff_id`, `staff_change_type_id`, `status_id`, `staff_id`, `staff_type_id`, `institution_id`, 0, `institution_position_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3253_institution_staff_position_profiles`;

-- training_courses
RENAME TABLE `training_courses` TO `z_3253_training_courses`;

DROP TABLE IF EXISTS `training_courses`;
CREATE TABLE IF NOT EXISTS `training_courses` (
  `id` int(11) NOT NULL,
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
  `id` int(11) NOT NULL,
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
  `id` int(11) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `training_session_results` (`id`, `training_session_id`, `assignee_id`, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `training_session_id`, 0, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3253_training_session_results`;
