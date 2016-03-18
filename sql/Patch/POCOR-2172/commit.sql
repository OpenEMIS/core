-- db_patches
INSERT INTO `db_patches` VALUES('POCOR-2172', NOW());

-- staff_statuses
CREATE TABLE `staff_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `staff_statuses` (`id`, `code`, `name`) VALUES (1, 'ASSIGNED', 'Assigned');
INSERT INTO `staff_statuses` (`id`, `code`, `name`) VALUES (2, 'END_OF_ASSIGNMENT', 'End of Assignment');

-- For staff_assignment
-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `created_user_id`, `created`) 
VALUES ('Institutions > Staff', 'Institution.Staff', 1, NOW());

-- Pre-insert workflow for Institution > Staff
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Institution.Staff';
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('ADD-STAFF-001', 'Add Staff', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'ADD-STAFF-001' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_steps` (`name`, `stage`, `is_editable`, `is_removable`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 0, 1, 1, @workflowId, 1, NOW()),
('Pending Approval', 1, 0, 0, @workflowId, 1, NOW()),
('Closed', 2, 0, 0, @workflowId, 1, NOW()),
('Assigned', NULL, 0, 0, @workflowId, 1, NOW()),
('Pending Termination', NULL, 0, 0, @workflowId, 1, NOW()),
('End of Assignment', NULL, 0, 0, @workflowId, 1, NOW());

SET @openStepId := 0;
SET @approvalStepId := 0;
SET @closedStepId := 0;
SET @activeStepId := 0;
SET @deactivateStepId := 0;
SET @inactiveStepId := 0;
SELECT `id` INTO @openStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Open' AND `stage` = 0;
SELECT `id` INTO @approvalStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending Approval' AND `stage` = 1;
SELECT `id` INTO @closedStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Closed' AND `stage` = 2;
SELECT `id` INTO @activeStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Assigned';
SELECT `id` INTO @deactivateStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending Termination';
SELECT `id` INTO @inactiveStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'End of Assignment';

INSERT INTO `workflow_actions` (`name`, `action`, `visible`, `next_workflow_step_id`, `event_key`, `comment_required`, `workflow_step_id`, `created_user_id`, `created`) VALUES
('Submit For Approval', 0, 1, @approvalStepId, '', 0, @openStepId, 1, NOW()),
('Cancel', 1, 1, @closedStepId, '', 1, @openStepId, 1, NOW()),
('Approve', 0, 1, @activeStepId, '', 0, @approvalStepId, 1, NOW()),
('Reject', 1, 1, @openStepId, '', 1, @approvalStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reopen', NULL, 1, @openStepId, '', 1, @closedStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @activeStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @activeStepId, 1, NOW()),
('Submit For Termination', NULL, 1, @deactivateStepId, '', 1, @activeStepId, 1, NOW()),
('Approve', 0, 1, @inactiveStepId, '', 0, @deactivateStepId, 1, NOW()),
('Reject', 1, 1, @activeStepId, '', 1, @deactivateStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @inactiveStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @inactiveStepId, 1, NOW()),
('Reactivate', NULL, 1, @approvalStepId, '', 1, @inactiveStepId, 1, NOW());

INSERT INTO `workflow_statuses` (`code`, `name`, `is_editable`, `is_removable`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('ASSIGNED', 'Assigned', 0, 0, @modelId, 1, NOW()),
('END_OF_ASSIGNMENT', 'End of Assignment', 0, 0, @modelId, 1, NOW());

SET @activeId := 0;
SET @inactiveId := 0;
SELECT `id` INTO @activeId FROM `workflow_statuses` WHERE `code` = 'ASSIGNED' AND `workflow_model_id` = @modelId;
SELECT `id` INTO @inactiveId FROM `workflow_statuses` WHERE `code` = 'END_OF_ASSIGNMENT' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_statuses_steps` (`id`, `workflow_status_id`, `workflow_step_id`) VALUES
(uuid(), @activeId, @activeStepId),
(uuid(), @inactiveId, @inactiveStepId);
-- End Pre-insert

-- For staff transfer in
-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `created_user_id`, `created`) 
VALUES ('Institutions > Staff > Transfer', 'Institution.StaffTransfer', 1, NOW());

-- Pre-insert workflow for Institution > Staff
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Institution.Staff';
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('TRANSFER-STAFF-001', 'Transfer Staff', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'ADD-STAFF-001' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_steps` (`name`, `stage`, `is_editable`, `is_removable`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 0, 1, 1, @workflowId, 1, NOW()),
('Pending Approval', 1, 0, 0, @workflowId, 1, NOW()),
('Closed', 2, 0, 0, @workflowId, 1, NOW()),
('Assigned', NULL, 0, 0, @workflowId, 1, NOW()),

SET @openStepId := 0;
SET @approvalStepId := 0;
SET @closedStepId := 0;
SET @activeStepId := 0;
SELECT `id` INTO @openStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Open' AND `stage` = 0;
SELECT `id` INTO @approvalStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending Approval' AND `stage` = 1;
SELECT `id` INTO @closedStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Closed' AND `stage` = 2;
SELECT `id` INTO @activeStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Assigned';

INSERT INTO `workflow_actions` (`name`, `action`, `visible`, `next_workflow_step_id`, `event_key`, `comment_required`, `workflow_step_id`, `created_user_id`, `created`) VALUES
('Submit For Approval', 0, 1, @approvalStepId, '', 0, @openStepId, 1, NOW()),
('Cancel', 1, 1, @closedStepId, '', 1, @openStepId, 1, NOW()),
('Approve', 0, 1, @activeStepId, '', 0, @approvalStepId, 1, NOW()),
('Reject', 1, 1, @openStepId, '', 1, @approvalStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reopen', NULL, 1, @openStepId, '', 1, @closedStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @activeStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @activeStepId, 1, NOW());

INSERT INTO `workflow_statuses` (`code`, `name`, `is_editable`, `is_removable`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('ASSIGNED', 'Assigned', 0, 0, @modelId, 1, NOW()),
('Pending', 'Pending Approval', 0, 0, @modelId, 1, NOW());

SET @activeId := 0;
SELECT `id` INTO @activeId FROM `workflow_statuses` WHERE `code` = 'ASSIGNED' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_statuses_steps` (`id`, `workflow_status_id`, `workflow_step_id`) VALUES
(uuid(), @activeId, @activeStepId);
-- End Pre-insert
