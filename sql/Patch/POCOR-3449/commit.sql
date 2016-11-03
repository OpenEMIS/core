-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3449', NOW());

-- create table
CREATE TABLE `staff_training_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `training_session_id` int(11) NOT NULL COMMENT 'links to training_sessions.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `training_session_id` (`training_session_id`),
  KEY `status_id` (`status_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `institution_id` (`institution_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the course applications for a particular staff';

-- workflow_models
SET @modelId := 10;
INSERT INTO `workflow_models` (`id`, `name`, `model`, `filter`, `is_school_based`, `created_user_id`, `created`) VALUES
(@modelId, 'Administration > Training > Applications', 'Training.TrainingApplications', NULL, 1, 1, NOW());

-- Pre-insert workflows
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('TRN-5001', 'Training Applications', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'TRN-5001';

-- Pre-insert workflow_steps
SET @openStatusId := 0;
SET @pendingApprovalStatusId := 0;
SET @closedStatusId := 0;
SET @pendingReviewStatusId := 0;
SET @approvedStatusId := 0;
SET @rejectedStatusId := 0;
INSERT INTO `workflow_steps` (`name`, `category`, `is_editable`, `is_removable`, `is_system_defined`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 1, 0, 1, 1, @workflowId, 1, NOW()),
('Pending For Approval', 2, 0, 0, 1, @workflowId, 1, NOW()),
('Withdrawn', 3, 0, 0, 1, @workflowId, 1, NOW()),
('Pending For Review', 0, 0, 0, 0, @workflowId, 1, NOW()),
('Approved', 0, 0, 0, 0, @workflowId, 1, NOW()),
('Rejected', 0, 0, 0, 0, @workflowId, 1, NOW());

SELECT `id` INTO @openStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 1;
SELECT `id` INTO @pendingApprovalStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 2;
SELECT `id` INTO @closedStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 3;
SELECT `id` INTO @pendingReviewStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending For Review';
SELECT `id` INTO @approvedStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Approved';
SELECT `id` INTO @rejectedStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Rejected';

-- Pre-insert workflow_actions
INSERT INTO `workflow_actions` (`name`, `description`, `action`, `visible`, `comment_required`, `allow_by_assignee`, `event_key`, `workflow_step_id`, `next_workflow_step_id`, `created_user_id`, `created`) VALUES
('Submit For Approval', NULL, 0, 1, 0, 1, NULL, @openStatusId, @pendingReviewStatusId, 1, NOW()),
('Approve', NULL, 0, 1, 0, 0, NULL, @pendingReviewStatusId, @pendingApprovalStatusId, 1, NOW()),
('Reject', NULL, 1, 1, 0, 0, NULL, @pendingReviewStatusId, @rejectedStatusId, 1, NOW()),
('Approve', NULL, 0, 1, 0, 0, 'Workflow.onAssignTrainingSession', @pendingApprovalStatusId, @approvedStatusId, 1, NOW()),
('Reject', NULL, 1, 1, 0, 0, NULL, @pendingApprovalStatusId, @rejectedStatusId, 1, NOW()),
('Withdraw From Training Session', NULL, NULL, 1, 0, 1, 'Workflow.onWithdrawTrainingSession', @approvedStatusId, @closedStatusId, 1, NOW());

-- Pre-insert workflow_statuses
INSERT INTO `workflow_statuses` (`code`, `name`, `is_editable`, `is_removable`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('PENDINGREVIEW', 'Pending Review', 0, 0, @modelId, 1, NOW()),
('PENDINGAPPROVAL', 'Pending Approval', 0, 0, @modelId, 1, NOW()),
('APPROVED', 'Approved', 0, 0, @modelId, 1, NOW()),
('REJECTED', 'Rejected', 0, 0, @modelId, 1, NOW());

-- Pre-insert workflow_statuses_steps
SET @pendingReviewId := 0;
SET @pendingApprovalId := 0;
SET @approvedId := 0;
SET @rejectedId := 0;
SELECT `id` INTO @pendingReviewId FROM `workflow_statuses` WHERE `code` = 'PENDINGREVIEW' AND `workflow_model_id` = @modelId;
SELECT `id` INTO @pendingApprovalId FROM `workflow_statuses` WHERE `code` = 'PENDINGAPPROVAL' AND `workflow_model_id` = @modelId;
SELECT `id` INTO @approvedId FROM `workflow_statuses` WHERE `code` = 'APPROVED' AND `workflow_model_id` = @modelId;
SELECT `id` INTO @rejectedId FROM `workflow_statuses` WHERE `code` = 'REJECTED' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_statuses_steps` (`id`, `workflow_status_id`, `workflow_step_id`) VALUES
('8b16b531-9c00-11e6-98d5-525400b263eb', @pendingReviewId, @pendingReviewStatusId),
('9c773e6a-9c00-11e6-98d5-525400b263eb', @pendingApprovalId, @pendingApprovalStatusId),
('a5ddc9ec-9c00-11e6-98d5-525400b263eb', @approvedId, @approvedStatusId),
('b0b1b8c1-9c00-11e6-98d5-525400b263eb', @rejectedId, @rejectedStatusId);

-- add security_functions
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(3037, 'Applications', 'Institutions', 'Institutions', 'Staff - Training', '3000', 'StaffTrainingApplications.index|StaffTrainingApplications.view|StaffTrainingApplications.download', null, 'StaffTrainingApplications.add|CourseCatalogue.index|CourseCatalogue.view|CourseCatalogue.download', 'StaffTrainingApplications.remove', null, 3037, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `order`, `visible`, `created_user_id`, `created`) VALUES
(5050, 'Applications', 'Trainings', 'Administration', 'Trainings', '5000', 'Applications.index|Applications.view', 5050, 1, 1, NOW());

-- update staff training security_functions
UPDATE `security_functions`
SET `controller` = 'Institutions', `_view` = 'StaffTrainingNeeds.index|StaffTrainingNeeds.view', `_edit` = 'StaffTrainingNeeds.edit', `_add` = 'StaffTrainingNeeds.add', `_delete` = 'StaffTrainingNeeds.remove'
WHERE `name` = 'Needs' AND `category` = 'Staff - Training';

UPDATE `security_functions`
SET `controller` = 'Institutions', `_view` = 'StaffTrainingResults.index|StaffTrainingResults.view'
WHERE `name` = 'Results' AND `category` = 'Staff - Training';

-- training_sessions_trainees
ALTER TABLE `training_sessions_trainees`
CHANGE COLUMN `id` `id` CHAR(64) NOT NULL ,
ADD COLUMN `status` INT(1) NULL COMMENT '1 -> Active, 2 -> Withdrawed' AFTER `trainee_id`,
ADD UNIQUE INDEX `id_UNIQUE` (`id`),
DROP PRIMARY KEY,
ADD PRIMARY KEY (`training_session_id`, `trainee_id`);

UPDATE `training_sessions_trainees`
SET `id` = sha2(concat(training_session_id, ',', trainee_id), 256), `status` = 1;

ALTER TABLE `training_sessions_trainees`
CHANGE COLUMN `status` `status` INT(1) NOT NULL COMMENT '1 -> Active, 2 -> Withdrawed';

-- workflow_models
UPDATE `workflow_models` SET `model`='Institution.StaffTrainingNeeds' WHERE `model`='Staff.TrainingNeeds';
