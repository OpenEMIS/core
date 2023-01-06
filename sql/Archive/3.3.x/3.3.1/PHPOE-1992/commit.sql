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
(uuid(), 'TrainingCourses', 'training_mode_of_delivery_id', 'Administration -> Training -> Course', 'Mode of Delivery', 1, 1, NOW());

-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `filter`, `created_user_id`, `created`) VALUES
('Administration > Training > Courses', 'Training.TrainingCourses', NULL, 1, NOW()),
('Administration > Training > Sessions', 'Training.TrainingSessions', NULL, 1, NOW()),
('Administration > Training > Results', 'Training.TrainingSessionResults', NULL, 1, NOW());

-- Workflow for Training > Courses
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Training.TrainingCourses';
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('TRN-1001', 'Training Courses', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'TRN-1001' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_steps` (`name`, `stage`, `is_editable`, `is_removable`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 0, 1, 1, @workflowId, 1, NOW()),
('Pending For Approval', 1, 0, 0, @workflowId, 1, NOW()),
('Closed', 2, 0, 0, @workflowId, 1, NOW()),
('Pending For Recommendation', NULL, 0, 0, @workflowId, 1, NOW()),
('Pending For Accreditation', NULL, 0, 0, @workflowId, 1, NOW()),
('Accredited', NULL, 0, 0, @workflowId, 1, NOW());

SET @openStepId := 0;
SET @approvalStepId := 0;
SET @closedStepId := 0;
SET @recommendStepId := 0;
SET @accreditationStepId := 0;
SET @accreditedStepId := 0;
SELECT `id` INTO @openStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Open' AND `stage` = 0;
SELECT `id` INTO @approvalStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending For Approval' AND `stage` = 1;
SELECT `id` INTO @closedStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Closed' AND `stage` = 2;
SELECT `id` INTO @recommendStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending For Recommendation';
SELECT `id` INTO @accreditationStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending For Accreditation';
SELECT `id` INTO @accreditedStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Accredited';

INSERT INTO `workflow_actions` (`name`, `action`, `visible`, `next_workflow_step_id`, `event_key`, `comment_required`, `workflow_step_id`, `created_user_id`, `created`) VALUES
('Submit For Recommendation', 0, 1, @recommendStepId, '', 0, @openStepId, 1, NOW()),
('Cancel', 1, 1, @closedStepId, '', 1, @openStepId, 1, NOW()),
('Submit For Accreditation', 0, 1, @accreditationStepId, '', 0, @approvalStepId, 1, NOW()),
('Reject', 1, 1, @openStepId, '', 1, @approvalStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reopen', NULL, 1, @openStepId, '', 1, @closedStepId, 1, NOW()),
('Submit For Approval', 0, 1, @approvalStepId, '', 0, @recommendStepId, 1, NOW()),
('Reject', 1, 1, @openStepId, '', 1, @recommendStepId, 1, NOW()),
('Accredit', 0, 1, @accreditedStepId, '', 0, @accreditationStepId, 1, NOW()),
('Reject', 1, 1, @openStepId, '', 1, @accreditationStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @accreditedStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @accreditedStepId, 1, NOW()),
('Inactive', NULL, 1, @closedStepId, '', 1, @accreditedStepId, 1, NOW());

INSERT INTO `workflow_statuses` (`code`, `name`, `is_editable`, `is_removable`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('PENDING', 'Pending', 0, 0, @modelId, 1, NOW()),
('APPROVED', 'Approved', 0, 0, @modelId, 1, NOW());

SET @pendingId := 0;
SET @approvedId := 0;
SELECT `id` INTO @pendingId FROM `workflow_statuses` WHERE `code` = 'PENDING' AND `workflow_model_id` = @modelId;
SELECT `id` INTO @approvedId FROM `workflow_statuses` WHERE `code` = 'APPROVED' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_statuses_steps` (`id`, `workflow_status_id`, `workflow_step_id`) VALUES
(uuid(), @pendingId, @recommendStepId),
(uuid(), @pendingId, @approvalStepId),
(uuid(), @pendingId, @accreditationStepId),
(uuid(), @approvedId, @accreditedStepId);

-- Workflow for Training > Sessions
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Training.TrainingSessions';
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('TRN-2001', 'Training Sessions', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'TRN-2001' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_steps` (`name`, `stage`, `is_editable`, `is_removable`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 0, 1, 1, @workflowId, 1, NOW()),
('Pending For Approval', 1, 0, 0, @workflowId, 1, NOW()),
('Closed', 2, 0, 0, @workflowId, 1, NOW()),
('Pending For Recommendation', NULL, 0, 0, @workflowId, 1, NOW()),
('Pending For Registration', NULL, 0, 0, @workflowId, 1, NOW()),
('Registered', NULL, 0, 0, @workflowId, 1, NOW());

SET @openStepId := 0;
SET @approvalStepId := 0;
SET @closedStepId := 0;
SET @recommendStepId := 0;
SET @registrationStepId := 0;
SET @registeredStepId := 0;
SELECT `id` INTO @openStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Open' AND `stage` = 0;
SELECT `id` INTO @approvalStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending For Approval' AND `stage` = 1;
SELECT `id` INTO @closedStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Closed' AND `stage` = 2;
SELECT `id` INTO @recommendStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending For Recommendation';
SELECT `id` INTO @registrationStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending For Registration';
SELECT `id` INTO @registeredStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Registered';

INSERT INTO `workflow_actions` (`name`, `action`, `visible`, `next_workflow_step_id`, `event_key`, `comment_required`, `workflow_step_id`, `created_user_id`, `created`) VALUES
('Submit For Recommendation', 0, 1, @recommendStepId, '', 0, @openStepId, 1, NOW()),
('Cancel', 1, 1, @closedStepId, '', 1, @openStepId, 1, NOW()),
('Submit For Registration', 0, 1, @registrationStepId, '', 0, @approvalStepId, 1, NOW()),
('Reject', 1, 1, @openStepId, '', 1, @approvalStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reopen', NULL, 1, @openStepId, '', 1, @closedStepId, 1, NOW()),
('Submit For Approval', 0, 1, @approvalStepId, '', 0, @recommendStepId, 1, NOW()),
('Reject', 1, 1, @openStepId, '', 1, @recommendStepId, 1, NOW()),
('Register', 0, 1, @registeredStepId, '', 0, @registrationStepId, 1, NOW()),
('Reject', 1, 1, @openStepId, '', 1, @registrationStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @registeredStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @registeredStepId, 1, NOW()),
('Inactive', NULL, 1, @closedStepId, '', 1, @registeredStepId, 1, NOW());

INSERT INTO `workflow_statuses` (`code`, `name`, `is_editable`, `is_removable`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('PENDING', 'Pending', 0, 0, @modelId, 1, NOW()),
('APPROVED', 'Approved', 0, 0, @modelId, 1, NOW());

SET @pendingId := 0;
SET @approvedId := 0;
SELECT `id` INTO @pendingId FROM `workflow_statuses` WHERE `code` = 'PENDING' AND `workflow_model_id` = @modelId;
SELECT `id` INTO @approvedId FROM `workflow_statuses` WHERE `code` = 'APPROVED' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_statuses_steps` (`id`, `workflow_status_id`, `workflow_step_id`) VALUES
(uuid(), @pendingId, @recommendStepId),
(uuid(), @pendingId, @approvalStepId),
(uuid(), @pendingId, @registrationStepId),
(uuid(), @approvedId, @registeredStepId);

-- Workflow for Training > Results
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Training.TrainingSessionResults';
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('TRN-3001', 'Training Results', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'TRN-3001' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_steps` (`name`, `stage`, `is_editable`, `is_removable`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 0, 1, 1, @workflowId, 1, NOW()),
('Pending For Approval', 1, 0, 0, @workflowId, 1, NOW()),
('Closed', 2, 0, 0, @workflowId, 1, NOW()),
('Pending For Evaluation', NULL, 0, 0, @workflowId, 1, NOW()),
('Pending For Posting', NULL, 0, 0, @workflowId, 1, NOW()),
('Posted', NULL, 0, 0, @workflowId, 1, NOW());

SET @openStepId := 0;
SET @approvalStepId := 0;
SET @closedStepId := 0;
SET @evaluationStepId := 0;
SET @postingStepId := 0;
SET @postedStepId := 0;
SELECT `id` INTO @openStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Open' AND `stage` = 0;
SELECT `id` INTO @approvalStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending For Approval' AND `stage` = 1;
SELECT `id` INTO @closedStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Closed' AND `stage` = 2;
SELECT `id` INTO @evaluationStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending For Evaluation';
SELECT `id` INTO @postingStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending For Posting';
SELECT `id` INTO @postedStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Posted';

INSERT INTO `workflow_actions` (`name`, `action`, `visible`, `next_workflow_step_id`, `event_key`, `comment_required`, `workflow_step_id`, `created_user_id`, `created`) VALUES
('Submit For Evaluation', 0, 1, @evaluationStepId, '', 0, @openStepId, 1, NOW()),
('Cancel', 1, 1, @closedStepId, '', 1, @openStepId, 1, NOW()),
('Submit For Posting', 0, 1, @postingStepId, '', 0, @approvalStepId, 1, NOW()),
('Reject', 1, 1, @openStepId, '', 1, @approvalStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reopen', NULL, 1, @openStepId, '', 1, @closedStepId, 1, NOW()),
('Submit For Approval', 0, 1, @approvalStepId, '', 0, @evaluationStepId, 1, NOW()),
('Reject', 1, 1, @openStepId, '', 1, @evaluationStepId, 1, NOW()),
('Post', 0, 1, @postedStepId, '', 0, @postingStepId, 1, NOW()),
('Reject', 1, 1, @openStepId, '', 1, @postingStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @postedStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @postedStepId, 1, NOW()),
('Inactive', NULL, 1, @closedStepId, '', 1, @postedStepId, 1, NOW());

INSERT INTO `workflow_statuses` (`code`, `name`, `is_editable`, `is_removable`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('PENDING', 'Pending', 0, 0, @modelId, 1, NOW()),
('APPROVED', 'Approved', 0, 0, @modelId, 1, NOW());

SET @pendingId := 0;
SET @approvedId := 0;
SELECT `id` INTO @pendingId FROM `workflow_statuses` WHERE `code` = 'PENDING' AND `workflow_model_id` = @modelId;
SELECT `id` INTO @approvedId FROM `workflow_statuses` WHERE `code` = 'APPROVED' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_statuses_steps` (`id`, `workflow_status_id`, `workflow_step_id`) VALUES
(uuid(), @pendingId, @evaluationStepId),
(uuid(), @pendingId, @approvalStepId),
(uuid(), @pendingId, @postingStepId),
(uuid(), @approvedId, @postedStepId);

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
UPDATE `field_options` SET `plugin` = 'Institution' WHERE `code` = 'StaffPositionTitles';

UPDATE `field_options` SET `visible` = 1 WHERE `parent` = 'Training';

-- delete TrainingStatuses
SET @parentId := 0;
SELECT `id` INTO @parentId FROM `field_options` WHERE `code` = 'TrainingStatuses';
DELETE FROM `field_option_values` WHERE `field_option_id` = @parentId;
DELETE FROM `field_options` WHERE `id` = @parentId;

-- purge and recreate TrainingResultTypes
SET @parentId := 0;
SELECT `id` INTO @parentId FROM `field_options` WHERE `code` = 'TrainingResultTypes';
DELETE FROM `field_option_values` WHERE `field_option_id` = @parentId;
INSERT INTO `field_option_values` (`name`, `order`, `visible`, `editable`, `default`, `field_option_id`, `created_user_id`, `created`) VALUES
('Exam', 1, 1, 1, 0, @parentId, 1, NOW()),
('Practical', 2, 1, 1, 0, @parentId, 1, NOW()),
('Attendance', 3, 1, 1, 0, @parentId, 1, NOW());

-- create TrainingSpecialisations
SET @ordering := 0;
SELECT `order` + 1 INTO @ordering FROM `field_options` WHERE `code` = 'TrainingResultTypes';
UPDATE `field_options` SET `order` = `order` + 1 WHERE `order` >= @ordering;
INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `created_user_id`, `created`) VALUES
('Training', 'TrainingSpecialisations', 'Specialisations', 'Training', NULL, @ordering, 1, 1, NOW());

-- Security function
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(5039, 'Courses', 'Trainings', 'Administration', 'Trainings', 5000,  'Courses.index|Courses.view', 'Courses.edit', 'Courses.add', 'Courses.remove', 'Courses.download', 5039, 1, 1, NOW()),
(5040, 'Sessions', 'Trainings', 'Administration', 'Trainings', 5000,  'Sessions.index|Sessions.view', 'Sessions.edit', 'Sessions.add', 'Sessions.remove', NULL, 5040, 1, 1, NOW()),
(5041, 'Results', 'Trainings', 'Administration', 'Trainings', 5000,  'Results.index|Results.view', 'Results.edit', NULL, 'Results.remove', NULL, 5041, 1, 1, NOW());
