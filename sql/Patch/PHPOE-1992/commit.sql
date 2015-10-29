-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1992', NOW());

-- Backup Admin - training tables
CREATE TABLE `z_1992_training_courses` LIKE `training_courses`;
CREATE TABLE `z_1992_training_course_attachments` LIKE `training_course_attachments`;
CREATE TABLE `z_1992_training_course_experiences` LIKE `training_course_experiences`;
CREATE TABLE `z_1992_training_course_prerequisites` LIKE `training_course_prerequisites`;
CREATE TABLE `z_1992_training_course_providers` LIKE `training_course_providers`;
CREATE TABLE `z_1992_training_course_result_types` LIKE `training_course_result_types`;
CREATE TABLE `z_1992_training_course_specialisations` LIKE `training_course_specialisations`;
CREATE TABLE `z_1992_training_course_target_populations` LIKE `training_course_target_populations`;
CREATE TABLE `z_1992_training_credit_hours` LIKE `training_credit_hours`;

CREATE TABLE `z_1992_training_sessions` LIKE `training_sessions`;
CREATE TABLE `z_1992_training_session_results` LIKE `training_session_results`;
CREATE TABLE `z_1992_training_session_trainees` LIKE `training_session_trainees`;
CREATE TABLE `z_1992_training_session_trainee_results` LIKE `training_session_trainee_results`;
CREATE TABLE `z_1992_training_session_trainers` LIKE `training_session_trainers`;

INSERT INTO `z_1992_training_courses` SELECT * FROM `training_courses` WHERE 1;
INSERT INTO `z_1992_training_course_attachments` SELECT * FROM `training_course_attachments` WHERE 1;
INSERT INTO `z_1992_training_course_experiences` SELECT * FROM `training_course_experiences` WHERE 1;
INSERT INTO `z_1992_training_course_prerequisites` SELECT * FROM `training_course_prerequisites` WHERE 1;
INSERT INTO `z_1992_training_course_providers` SELECT * FROM `training_course_providers` WHERE 1;
INSERT INTO `z_1992_training_course_result_types` SELECT * FROM `training_course_result_types` WHERE 1;
INSERT INTO `z_1992_training_course_specialisations` SELECT * FROM `training_course_specialisations` WHERE 1;
INSERT INTO `z_1992_training_course_target_populations` SELECT * FROM `training_course_target_populations` WHERE 1;
INSERT INTO `z_1992_training_credit_hours` SELECT * FROM `training_credit_hours` WHERE 1;

INSERT INTO `z_1992_training_sessions` SELECT * FROM `training_sessions` WHERE 1;
INSERT INTO `z_1992_training_session_results` SELECT * FROM `training_session_results` WHERE 1;
INSERT INTO `z_1992_training_session_trainees` SELECT * FROM `training_session_trainees` WHERE 1;
INSERT INTO `z_1992_training_session_trainee_results` SELECT * FROM `training_session_trainee_results` WHERE 1;
INSERT INTO `z_1992_training_session_trainers` SELECT * FROM `training_session_trainers` WHERE 1;

DROP TABLE IF EXISTS `training_courses`;
DROP TABLE IF EXISTS `training_course_attachments`;
DROP TABLE IF EXISTS `training_course_experiences`;
DROP TABLE IF EXISTS `training_course_prerequisites`;
DROP TABLE IF EXISTS `training_course_providers`;
DROP TABLE IF EXISTS `training_course_result_types`;
DROP TABLE IF EXISTS `training_course_specialisations`;
DROP TABLE IF EXISTS `training_course_target_populations`;
DROP TABLE IF EXISTS `training_credit_hours`;

DROP TABLE IF EXISTS `training_sessions`;
DROP TABLE IF EXISTS `training_session_results`;
DROP TABLE IF EXISTS `training_session_trainees`;
DROP TABLE IF EXISTS `training_session_trainee_results`;
DROP TABLE IF EXISTS `training_session_trainers`;

-- New table - training_courses
DROP TABLE IF EXISTS `training_courses`;
CREATE TABLE IF NOT EXISTS `training_courses` (
  `id` int(11) NOT NULL,
  `code` varchar(60) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `objective` text DEFAULT NULL,
  `credit_hours` int(3) NOT NULL,
  `duration` int(3) NOT NULL,
  `number_of_months` int(3) NOT NULL,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob DEFAULT NULL,
  `training_field_of_study_id` int(11) NOT NULL,
  `training_course_type_id` int(11) NOT NULL,
  `training_mode_of_delivery_id` int(11) NOT NULL,
  `training_requirement_id` int(11) NOT NULL,
  `training_level_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `training_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_field_of_study_id` (`training_field_of_study_id`),
  ADD KEY `training_course_type_id` (`training_course_type_id`),
  ADD KEY `training_mode_of_delivery_id` (`training_mode_of_delivery_id`),
  ADD KEY `training_requirement_id` (`training_requirement_id`),
  ADD KEY `training_level_id` (`training_level_id`),
  ADD KEY `status_id` (`status_id`);

ALTER TABLE `training_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - training_courses_target_populations
DROP TABLE IF EXISTS `training_courses_target_populations`;
CREATE TABLE IF NOT EXISTS `training_courses_target_populations` (
  `id` char(36) NOT NULL,
  `training_course_id` int(11) NOT NULL,
  `target_population_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `training_courses_target_populations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_course_id` (`training_course_id`),
  ADD KEY `target_population_id` (`target_population_id`);

-- New table - training_courses_providers
DROP TABLE IF EXISTS `training_courses_providers`;
CREATE TABLE IF NOT EXISTS `training_courses_providers` (
  `id` char(36) NOT NULL,
  `training_course_id` int(11) NOT NULL,
  `training_provider_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `training_courses_providers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_course_id` (`training_course_id`),
  ADD KEY `training_provider_id` (`training_provider_id`);

-- New table - training_courses_prerequisites
DROP TABLE IF EXISTS `training_courses_prerequisites`;
CREATE TABLE IF NOT EXISTS `training_courses_prerequisites` (
  `id` char(36) NOT NULL,
  `training_course_id` int(11) NOT NULL,
  `prerequisite_training_course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `training_courses_prerequisites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_course_id` (`training_course_id`),
  ADD KEY `prerequisite_training_course_id` (`prerequisite_training_course_id`);

-- New table - training_courses_specialisations
DROP TABLE IF EXISTS `training_courses_specialisations`;
CREATE TABLE IF NOT EXISTS `training_courses_specialisations` (
  `id` char(36) NOT NULL,
  `training_course_id` int(11) NOT NULL,
  `training_specialisation_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `training_courses_specialisations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_course_id` (`training_course_id`),
  ADD KEY `training_specialisation_id` (`training_specialisation_id`);

-- New table - training_courses_result_types
DROP TABLE IF EXISTS `training_courses_result_types`;
CREATE TABLE IF NOT EXISTS `training_courses_result_types` (
  `id` char(36) NOT NULL,
  `training_course_id` int(11) NOT NULL,
  `training_result_type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `training_courses_result_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_course_id` (`training_course_id`),
  ADD KEY `training_result_type_id` (`training_result_type_id`);

-- New table - training_sessions
DROP TABLE IF EXISTS `training_sessions`;
CREATE TABLE IF NOT EXISTS `training_sessions` (
  `id` int(11) NOT NULL,
  `code` varchar(60) NOT NULL,
  `name` varchar(250) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `comment` text DEFAULT NULL,
  `training_course_id` int(11) NOT NULL,
  `training_provider_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `training_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_course_id` (`training_course_id`),
  ADD KEY `training_provider_id` (`training_provider_id`),
  ADD KEY `status_id` (`status_id`);


ALTER TABLE `training_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - training_session_trainers
DROP TABLE IF EXISTS `training_session_trainers`;
CREATE TABLE IF NOT EXISTS `training_session_trainers` (
  `id` char(36) NOT NULL,
  `type` varchar(20) NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `training_session_id` int(11) NOT NULL,
  `trainer_id` int(11) DEFAULT NULL COMMENT 'links to security_users.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `training_session_trainers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_session_id` (`training_session_id`),
  ADD KEY `trainer_id` (`trainer_id`);

-- New table - training_sessions_trainees
DROP TABLE IF EXISTS `training_sessions_trainees`;
CREATE TABLE IF NOT EXISTS `training_sessions_trainees` (
  `id` char(36) NOT NULL,
  `training_session_id` int(11) NOT NULL,
  `trainee_id` int(11) NOT NULL COMMENT 'links to security_users.id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `training_sessions_trainees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_session_id` (`training_session_id`),
  ADD KEY `trainee_id` (`trainee_id`);

-- New table - training_session_results
DROP TABLE IF EXISTS `training_session_results`;
CREATE TABLE IF NOT EXISTS `training_session_results` (
  `id` int(11) NOT NULL,
  `training_session_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `training_session_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_session_id` (`training_session_id`),
  ADD KEY `status_id` (`status_id`);

ALTER TABLE `training_session_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - training_session_trainee_results
DROP TABLE IF EXISTS `training_session_trainee_results`;
CREATE TABLE IF NOT EXISTS `training_session_trainee_results` (
  `id` char(36) NOT NULL,
  `result` varchar(10) DEFAULT NULL,
  `training_result_type_id` int(11) NOT NULL,
  `trainee_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `training_session_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `training_session_trainee_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_result_type_id` (`training_result_type_id`),
  ADD KEY `trainee_id` (`trainee_id`),
  ADD KEY `training_session_id` (`training_session_id`);

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES
(uuid(), 'TrainingCourses', 'number_of_months', 'Administration -> Training -> Course', 'Experiences', 1, 1, NOW()),
(uuid(), 'TrainingCourses', 'file_content', 'Administration -> Training -> Course', 'Attachment', 1, 1, NOW()),
(uuid(), 'TrainingCourses', 'training_field_of_study_id', 'Administration -> Training -> Course', 'Field of Study', 1, 1, NOW()),
(uuid(), 'TrainingCourses', 'training_course_type_id', 'Administration -> Training -> Course', 'Course Type', 1, 1, NOW()),
(uuid(), 'TrainingCourses', 'training_mode_of_delivery_id', 'Administration -> Training -> Course', 'Mode of Delivery', 1, 1, NOW()),
(uuid(), 'TrainingSessionResults', 'training_result_type', 'Administration -> Training -> Results', 'Result Type', 1, 1, NOW());

-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `filter`, `created_user_id`, `created`) VALUES
('Administration > Training > Courses', 'Training.TrainingCourses', NULL, 1, NOW()),
('Administration > Training > Sessions', 'Training.TrainingSessions', NULL, 1, NOW()),
('Administration > Training > Results', 'Training.TrainingSessionResults', NULL, 1, NOW());

-- field_options
UPDATE `field_options` SET `plugin` = 'Training' WHERE `code` = 'TrainingAchievementTypes';
UPDATE `field_options` SET `plugin` = 'Training' WHERE `code` = 'TrainingCourseTypes';
UPDATE `field_options` SET `plugin` = 'Training' WHERE `code` = 'TrainingFieldStudies';
UPDATE `field_options` SET `plugin` = 'Training' WHERE `code` = 'TrainingLevels';
UPDATE `field_options` SET `plugin` = 'Training' WHERE `code` = 'TrainingModeDeliveries';
UPDATE `field_options` SET `plugin` = 'Training' WHERE `code` = 'TrainingNeedCategories';
UPDATE `field_options` SET `plugin` = 'Training' WHERE `code` = 'TrainingPriorities';
UPDATE `field_options` SET `plugin` = 'Training' WHERE `code` = 'TrainingProviders';
UPDATE `field_options` SET `plugin` = 'Training' WHERE `code` = 'TrainingRequirements';
UPDATE `field_options` SET `plugin` = 'Training' WHERE `code` = 'TrainingResultTypes';
UPDATE `field_options` SET `plugin` = 'Training' WHERE `code` = 'TrainingStatuses';

INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `created_user_id`, `created`) VALUES
('Training', 'TrainingSpecialisations', 'Specialisations', 'Training', NULL, 0, 1, 1, NOW());

UPDATE `field_options` SET `visible` = 1 WHERE `parent` = 'Training';
UPDATE `field_options` SET `visible` = 0 WHERE `code` = 'TrainingStatuses';
