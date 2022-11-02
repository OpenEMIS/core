-- POCOR-2172
-- db_patches
INSERT INTO `db_patches` VALUES('POCOR-2172', NOW());

-- staff_statuses
DROP TABLE IF EXISTS `staff_statuses`;
CREATE TABLE `staff_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `staff_statuses` (`id`, `code`, `name`) VALUES (1, 'ASSIGNED', 'Assigned');
INSERT INTO `staff_statuses` (`id`, `code`, `name`) VALUES (2, 'END_OF_ASSIGNMENT', 'End of Assignment');

CREATE TABLE `staff_change_types` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`));

INSERT INTO `staff_change_types` (`id`, `code`, `name`) VALUES (1, 'END_OF_ASSIGNMENT', 'End of Assignment');
INSERT INTO `staff_change_types` (`id`, `code`, `name`) VALUES (2, 'CHANGE_IN_FTE', 'Change in FTE');
INSERT INTO `staff_change_types` (`id`, `code`, `name`) VALUES (3, 'CHANGE_IN_STAFF_TYPE', 'Change in Staff Type');

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
  `staff_change_type_id` int(11) NOT NULL,
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
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- staff_assignments
CREATE TABLE `institution_staff_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_date` date NOT NULL,
  `end_date` date NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `status` int(11) NOT NULL COMMENT '0 -> New, 1 -> Approved, 2 -> Rejected, 3 -> Closed (For fixed workflow)',
  `staff_type_id` int(5) NOT NULL,
  `institution_id` varchar(45) NOT NULL,
  `institution_position_id` int(11) NOT NULL,
  `FTE` decimal(5,2) NOT NULL,
  `previous_institution_id` int(11) DEFAULT NULL,
  `comment` text,
  `type` int(11) NOT NULL COMMENT '1 -> Staff Assignment, 2 -> Staff Transfer',
  `update` int(1) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `institution_id` (`institution_id`),
  KEY `institution_position_id` (`institution_position_id`),
  KEY `previous_institution_id` (`previous_institution_id`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferRequests', 'previous_institution_id', 'Institution -> Staff Transfer Requests', 'Currently Assigned To', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferRequests', 'institution_id', 'Institution -> Staff Transfer Requests', 'Requested By', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'previous_institution_id', 'Institution -> Staff Transfer Approvals', 'To Be Approved By', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'institution_id', 'Institution -> Staff Transfer Approvals', 'Requested By', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffPositionProfiles', 'FTE', 'Institutions -> Staff -> Change in Assignment', 'New FTE', 1, 1, NOW());

-- security_functions
INSERT INTO security_functions(`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (1039, 'Transfer Requests', 'Institutions', 'Institutions', 'Staff', 8, 'StaffTransferRequests.index|StaffTransferRequests.view', 'StaffTransferRequests.remove', 'StaffTransferRequests.edit|StaffTransferRequests.add', 1039, 1, 1, NOW());

INSERT INTO security_functions(`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (1040, 'Transfer Approvals', 'Institutions', 'Institutions', 'Staff', 8, 'StaffTransferApprovals.index|StaffTransferApprovals.view', 'StaffTransferApprovals.edit|StaffTransferApprovals.view', 1040, 1, 1, NOW());

INSERT INTO security_functions(`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`)
VALUES (1041, 'Change in Staff Assignment', 'Institutions', 'Institutions', 'Staff', 8, 'StaffPositionProfiles.index|StaffPositionProfiles.view', 'StaffPositionProfiles.edit', 'StaffPositionProfiles.add', 'StaffPositionProfiles.remove', 1041, 1, 1, NOW());

-- For staff_position_profiles
-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `created_user_id`, `created`)
VALUES ('Institutions > Staff > Change in Assignment', 'Institution.StaffPositionProfiles', 1, NOW());

-- Pre-insert workflow for Institution > Staff
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Institution.StaffPositionProfiles';
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('STAFF-POSITION-PROFILE-01', 'Staff Position Profile', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'STAFF-POSITION-PROFILE-01' AND `workflow_model_id` = @modelId;
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
('CLOSED', 'Closed', 0, 0, @modelId, 1, NOW()),
('APPROVED', 'Approved', 0, 0, @modelId, 1, NOW());

SET @activeId := 0;
SET @closeId = 0;
SELECT `id` INTO @activeId FROM `workflow_statuses` WHERE `code` = 'APPROVED' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_statuses_steps` (`id`, `workflow_status_id`, `workflow_step_id`) VALUES
(uuid(), @activeId, @activeStepId);

SELECT `id` INTO @closeId FROM `workflow_statuses` WHERE `code` = 'CLOSED' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_statuses_steps` (`id`, `workflow_status_id`, `workflow_step_id`) VALUES
(uuid(), @closeId, @closedStepId);
-- End Pre-insert

-- add missing index on institution_students
ALTER TABLE `institution_students` ADD INDEX(`student_status_id`);

-- Set LDAP Authentication to be not visible
UPDATE config_item_options SET `visible` = 0 WHERE `option_type` = 'authentication_type' AND `option` = 'LDAP';


-- POCOR-2786
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2786', NOW());

-- security_users
CREATE TABLE `z_2786_security_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(1) NOT NULL,
  `date_of_birth` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_2786_security_users` (`id`, `gender_id`, `date_of_birth`)
SELECT `id`, `gender_id`, `date_of_birth` FROM `security_users` WHERE `gender_id` = 0;

SET @genderId := 0;
SELECT `id` INTO @genderId FROM `genders` WHERE `name` = 'Male';

UPDATE `security_users` SET `gender_id` = @genderId WHERE `gender_id` = 0;

INSERT IGNORE INTO `z_2786_security_users` (`id`, `gender_id`, `date_of_birth`)
SELECT `id`, `gender_id`, `date_of_birth` FROM `security_users` WHERE `date_of_birth` = '0000-00-00';

UPDATE `security_users` SET `date_of_birth` = '1900-01-01' WHERE `date_of_birth` = '0000-00-00';


-- 3.5.1
UPDATE config_items SET value = '3.5.1' WHERE code = 'db_version';
