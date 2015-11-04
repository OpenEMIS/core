-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1978', NOW());

-- Backup Admin - training tables
CREATE TABLE `z_1978_staff_training_needs` LIKE `staff_training_needs`;
CREATE TABLE `z_1978_staff_training_self_studies` LIKE `staff_training_self_studies`;
CREATE TABLE `z_1978_staff_training_self_study_attachments` LIKE `staff_training_self_study_attachments`;
CREATE TABLE `z_1978_staff_training_self_study_results` LIKE `staff_training_self_study_results`;

INSERT INTO `z_1978_staff_training_needs` SELECT * FROM `staff_training_needs` WHERE 1;
INSERT INTO `z_1978_staff_training_self_studies` SELECT * FROM `staff_training_self_studies` WHERE 1;
INSERT INTO `z_1978_staff_training_self_study_attachments` SELECT * FROM `staff_training_self_study_attachments` WHERE 1;
INSERT INTO `z_1978_staff_training_self_study_results` SELECT * FROM `staff_training_self_study_results` WHERE 1;

DROP TABLE IF EXISTS `staff_training_needs`;
DROP TABLE IF EXISTS `staff_training_self_studies`;
DROP TABLE IF EXISTS `staff_training_self_study_attachments`;
DROP TABLE IF EXISTS `staff_training_self_study_results`;

-- New table - staff_training_needs
DROP TABLE IF EXISTS `staff_training_needs`;
CREATE TABLE `staff_training_needs` (
  `id` int(11) NOT NULL,
  `comments` text DEFAULT NULL,
  `course_code` varchar(60) NOT NULL,
  `course_name` varchar(250) NOT NULL,
  `course_description` text DEFAULT NULL,
  `course_id` int(11) NOT NULL COMMENT 'links to training_courses.id',
  `training_need_category_id` int(11) NOT NULL,
  `training_requirement_id` int(11) NOT NULL,
  `training_priority_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `staff_training_needs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `training_need_category_id` (`training_need_category_id`),
  ADD KEY `training_requirement_id` (`training_requirement_id`),
  ADD KEY `training_priority_id` (`training_priority_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `status_id` (`status_id`);

ALTER TABLE `staff_training_needs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - staff_training_self_studies
DROP TABLE IF EXISTS `staff_training_self_studies`;
CREATE TABLE `staff_training_self_studies` (
  `id` int(11) NOT NULL,
  `code` varchar(60) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `objective` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `credit_hours` int(3) NOT NULL,
  `duration` int(3) NOT NULL,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob DEFAULT NULL,
  `training_achievement_type_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `staff_training_self_studies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_achievement_type_id` (`training_achievement_type_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `status_id` (`status_id`);

ALTER TABLE `staff_training_self_studies` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES
(uuid(), 'Achievements', 'file_content', 'Staff -> Training -> Achievements', 'Attachment', 1, 1, NOW());

-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `filter`, `created_user_id`, `created`) VALUES
('Staff > Training > Needs', 'Staff.TrainingNeeds', NULL, 1, NOW()),
('Staff > Training > Achievements', 'Staff.Achievements', NULL, 1, NOW());

-- Workflow for Training > Needs
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Staff.TrainingNeeds';
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('TRN-4001', 'Training Needs', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'TRN-4001' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_steps` (`name`, `stage`, `is_editable`, `is_removable`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 0, 1, 1, @workflowId, 1, NOW()),
('Pending For Approval', 1, 0, 0, @workflowId, 1, NOW()),
('Closed', 2, 0, 0, @workflowId, 1, NOW()),
('Approved', NULL, 0, 0, @workflowId, 1, NOW());

SET @openStepId := 0;
SET @approvalStepId := 0;
SET @closedStepId := 0;
SET @approvedStepId := 0;
SELECT `id` INTO @openStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Open' AND `stage` = 0;
SELECT `id` INTO @approvalStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending For Approval' AND `stage` = 1;
SELECT `id` INTO @closedStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Closed' AND `stage` = 2;
SELECT `id` INTO @approvedStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Approved';

INSERT INTO `workflow_actions` (`name`, `action`, `visible`, `next_workflow_step_id`, `event_key`, `comment_required`, `workflow_step_id`, `created_user_id`, `created`) VALUES
('Submit For Approval', 0, 1, @approvalStepId, '', 0, @openStepId, 1, NOW()),
('Cancel', 1, 1, @closedStepId, '', 1, @openStepId, 1, NOW()),
('Approve', 0, 1, @approvedStepId, '', 0, @approvalStepId, 1, NOW()),
('Reject', 1, 1, @openStepId, '', 1, @approvalStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reopen', NULL, 1, @openStepId, '', 1, @closedStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @approvedStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @approvedStepId, 1, NOW()),
('Inactive', NULL, 1, @closedStepId, '', 1, @approvedStepId, 1, NOW());

INSERT INTO `workflow_statuses` (`code`, `name`, `is_editable`, `is_removable`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('PENDING', 'Pending', 0, 0, @modelId, 1, NOW()),
('APPROVED', 'Approved', 0, 0, @modelId, 1, NOW());

SET @pendingId := 0;
SET @approvedId := 0;
SELECT `id` INTO @pendingId FROM `workflow_statuses` WHERE `code` = 'PENDING' AND `workflow_model_id` = @modelId;
SELECT `id` INTO @approvedId FROM `workflow_statuses` WHERE `code` = 'APPROVED' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_statuses_steps` (`id`, `workflow_status_id`, `workflow_step_id`) VALUES
(uuid(), @pendingId, @approvalStepId),
(uuid(), @approvedId, @approvedStepId);

-- Workflow for Training > Achievements
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Staff.Achievements';
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('TRN-5001', 'Training Courses', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'TRN-5001' AND `workflow_model_id` = @modelId;
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
