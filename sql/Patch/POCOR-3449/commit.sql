-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3449', NOW());

-- create table
CREATE TABLE `staff_training_applications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `training_course_id` int(11) NOT NULL COMMENT 'links to training_courses.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `training_course_id` (`training_course_id`),
  KEY `status_id` (`status_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `institution_id` (`institution_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the course applications for a particular staff';

-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `filter`, `is_school_based`, `created_user_id`, `created`) VALUES
('Staff > Training > Applications', 'Institution.StaffTrainingApplications', NULL, 0, 1, NOW());

-- Pre-insert workflows
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Institution.StaffTrainingApplications';
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
('Open', 1, 1, 1, 1, @workflowId, 1, NOW()),
('Pending For Approval', 2, 0, 0, 1, @workflowId, 1, NOW()),
('Closed', 3, 0, 0, 1, @workflowId, 1, NOW()),
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
('Approve', NULL, 0, 1, 0, 0, NULL, @pendingApprovalStatusId, @approvedStatusId, 1, NOW()),
('Reject', NULL, 1, 1, 0, 0, NULL, @pendingApprovalStatusId, @rejectedStatusId, 1, NOW());

-- still have to add statuses and steps_roles
