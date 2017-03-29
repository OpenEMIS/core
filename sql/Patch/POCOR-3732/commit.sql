-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3732', NOW());

-- institution_workflows
DROP TABLE IF EXISTS `institution_workflows`;
CREATE TABLE IF NOT EXISTS `institution_workflows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `feature` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `feature` (`feature`),
  KEY `status_id` (`status_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `institution_id` (`institution_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the workflows in a particular institution';

-- institution_workflows_records
DROP TABLE IF EXISTS `institution_workflows_records`;
CREATE TABLE IF NOT EXISTS `institution_workflows_records` (
  `id` char(36) NOT NULL,
  `institution_workflow_id` int(11) NOT NULL COMMENT 'links to institution_workflows.id',
  `record_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_workflow_id` (`institution_workflow_id`),
  KEY `record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains the list of records associates with institution_workflows';

-- workflow_models
SET @modelId := 12;
INSERT INTO `workflow_models` (`id`, `name`, `model`, `filter`, `is_school_based`, `created_user_id`, `created`) VALUES
(@modelId, 'Institutions > Staff > Behaviours', 'Institution.StaffBehaviours', 'Student.BehaviourClassifications', 1, 1, NOW());

-- Pre-insert workflows - Apply To All
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('STAFF-BEHAVIOUR-1001', 'Staff Behaviours - General', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'STAFF-BEHAVIOUR-1001';

INSERT INTO `workflows_filters` (`id`, `workflow_id`, `filter_id`) VALUES
('1f797824-1060-11e7-a3e1-525400b263eb', @workflowId, 0);

-- Pre-insert workflow_steps
SET @openStatusId := 0;
SET @pendingStatusId := 0;
SET @closedStatusId := 0;
SET @approvedStatusId := 0;
INSERT INTO `workflow_steps` (`name`, `category`, `is_editable`, `is_removable`, `is_system_defined`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 1, 1, 1, 1, @workflowId, 1, NOW()),
('Pending For Approval', 2, 0, 0, 1, @workflowId, 1, NOW()),
('Closed', 3, 0, 0, 1, @workflowId, 1, NOW()),
('Approved', 3, 0, 0, 0, @workflowId, 1, NOW());

SELECT `id` INTO @openStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 1;
SELECT `id` INTO @pendingStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 2;
SELECT `id` INTO @closedStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 3 AND `name` = 'Closed';
SELECT `id` INTO @approvedStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 3 AND `name` = 'Approved';

-- Pre-insert workflow_actions
INSERT INTO `workflow_actions` (`name`, `description`, `action`, `visible`, `comment_required`, `allow_by_assignee`, `event_key`, `workflow_step_id`, `next_workflow_step_id`, `created_user_id`, `created`) VALUES
('Submit For Approval', NULL, 0, 1, 0, 1, NULL, @openStatusId, @pendingStatusId, 1, NOW()),
('Cancel', NULL, 1, 1, 0, 1, NULL, @openStatusId, @closedStatusId, 1, NOW()),
('Approve', NULL, 0, 1, 0, 0, NULL, @pendingStatusId, @approvedStatusId, 1, NOW()),
('Reject', NULL, 1, 1, 0, 0, NULL, @pendingStatusId, @openStatusId, 1, NOW()),
('Approve', NULL, 0, 0, 0, 0, NULL, @closedStatusId, 0, 1, NOW()),
('Reject', NULL, 1, 0, 0, 0, NULL, @closedStatusId, 0, 1, NOW()),
('Reopen', NULL, NULL, 1, 0, 0, NULL, @closedStatusId, @openStatusId, 1, NOW()),
('Approve', NULL, 0, 0, 0, 0, NULL, @approvedStatusId, 0, 1, NOW()),
('Reject', NULL, 1, 0, 0, 0, NULL, @approvedStatusId, 0, 1, NOW()),
('Reopen', NULL, NULL, 1, 0, 0, NULL, @approvedStatusId, @openStatusId, 1, NOW());

-- security_functions
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(1056, 'Workflows', 'Institutions', 'Institutions', 'Workflows', 1000, 'Workflows.index|Workflows.view', NULL, NULL, NULL, NULL, 1056, 1, 1, NOW());
