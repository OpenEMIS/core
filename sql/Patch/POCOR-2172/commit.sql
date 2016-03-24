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

CREATE TABLE `z_2172_institution_staff` LIKE `institution_staff`;

INSERT INTO `z_2172_institution_staff`
SELECT * FROM `institution_staff`;

UPDATE `institution_staff` SET staff_status_id = 1
WHERE end_date IS NULL OR end_date >= NOW();

UPDATE `institution_staff` SET staff_status_id = 2
WHERE end_date < NOW();

UPDATE `field_options` SET `visible`='0' WHERE `plugin` = 'FieldOption' AND `code` = 'StaffStatuses';

-- institution_staff_position_profiles
CREATE TABLE `institution_staff_position_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_staff_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `FTE` decimal(5,2) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `staff_type_id` int(5) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `institution_position_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_type_id` (`staff_type_id`),
  KEY `institution_position_id` (`institution_position_id`),
  KEY `staff_id` (`staff_id`),
  KEY `institution_id` (`institution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- staff_assignments
CREATE TABLE `staff_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `status_id` int(11) NOT NULL,
  `institution_id` varchar(45) NOT NULL,
  `institution_position_id` int(11) NOT NULL,
  `fte` decimal(3,2) NOT NULL,
  `requesting_institution_id` int(11) DEFAULT NULL,
  `requesting_position_id` int(11) DEFAULT NULL,
  `comment` text,
  `type` int(11) NOT NULL COMMENT '1 -> Staff Assignment, 2 -> Staff Transfer',
  `updated` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `institution_id` (`institution_id`),
  KEY `institution_position_id` (`institution_position_id`),
  KEY `requesting_institution_id` (`requesting_institution_id`),
  KEY `requesting_position_id` (`requesting_position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- For staff_end_of_assignment
-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `created_user_id`, `created`) 
VALUES ('Institutions > Staff > Amend Staff Position Profile', 'Institution.StaffPositionProfiles', 1, NOW());

-- Pre-insert workflow for Institution > Staff
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Institution.StaffPositionProfiles';
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('AMEND-STAFF-001', 'Amend Staff Position Profile', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'END-STAFF-001' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_steps` (`name`, `stage`, `is_editable`, `is_removable`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 0, 1, 1, @workflowId, 1, NOW()),
('Pending Approval', 1, 0, 0, @workflowId, 1, NOW()),
('Closed', 2, 0, 0, @workflowId, 1, NOW()),
('Approved', NULL, 0, 0, @workflowId, 1, NOW());

SET @openStepId := 0;
SET @approvalStepId := 0;
SET @closedStepId := 0;
SET @activeStepId := 0;
SELECT `id` INTO @openStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Open' AND `stage` = 0;
SELECT `id` INTO @approvalStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending Approval' AND `stage` = 1;
SELECT `id` INTO @closedStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Closed' AND `stage` = 2;
SELECT `id` INTO @activeStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Approved';

INSERT INTO `workflow_actions` (`name`, `action`, `visible`, `next_workflow_step_id`, `event_key`, `comment_required`, `workflow_step_id`, `created_user_id`, `created`) VALUES
('Submit For Approval', 0, 1, @approvalStepId, '', 0, @openStepId, 1, NOW()),
('Cancel', 1, 1, @closedStepId, '', 1, @openStepId, 1, NOW()),
('Approve', 0, 1, @activeStepId, 'Workflow.onApprove', 0, @approvalStepId, 1, NOW()),
('Reject', 1, 1, @openStepId, '', 1, @approvalStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reopen', NULL, 1, @openStepId, '', 1, @closedStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @activeStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @activeStepId, 1, NOW());

INSERT INTO `workflow_statuses` (`code`, `name`, `is_editable`, `is_removable`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('PENDING', 'Pending', 0, 0, @modelId, 1, NOW()),
('APPROVED', 'Approved', 0, 0, @modelId, 1, NOW());

SET @activeId := 0;
SET @inactiveId := 0;
SELECT `id` INTO @activeId FROM `workflow_statuses` WHERE `code` = 'APPROVED' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_statuses_steps` (`id`, `workflow_status_id`, `workflow_step_id`) VALUES
(uuid(), @activeId, @activeStepId);
-- End Pre-insert
