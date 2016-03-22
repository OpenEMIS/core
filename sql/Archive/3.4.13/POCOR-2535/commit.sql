-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2535', NOW());

-- Backup table
CREATE TABLE `z_2535_institution_positions` LIKE  `institution_positions`;
INSERT INTO `z_2535_institution_positions` SELECT * FROM `institution_positions` WHERE 1;

-- Start: staff_position_grades
DROP TABLE IF EXISTS `staff_position_grades`;
CREATE TABLE IF NOT EXISTS `staff_position_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- update field_options
UPDATE `field_options` SET `params` = '{"model":"Institution.StaffPositionGrades"}' WHERE `code` = 'StaffPositionGrades';

-- move out staff_position_grades from field_option_values and start with new id
INSERT INTO `staff_position_grades` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`,`created`)
SELECT `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `id`, `created` FROM `field_option_values`
WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'StaffPositionGrades');
UPDATE `field_option_values` SET `visible` = 0 WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'StaffPositionGrades');

-- update new id back to field_option_values
UPDATE `field_option_values` AS `FieldOptionValues`
INNER JOIN `staff_position_grades` AS `StaffPositionGrades` ON `StaffPositionGrades`.`created_user_id` = `FieldOptionValues`.`id`
SET `FieldOptionValues`.`id_new` = `StaffPositionGrades`.`id`;

-- pacth new id to all hasMany tables
UPDATE `institution_positions` AS `InstitutionPositions`
INNER JOIN `field_option_values` AS `FieldOptionValues` ON `FieldOptionValues`.`id` = `InstitutionPositions`.`staff_position_grade_id`
SET `InstitutionPositions`.`staff_position_grade_id` = `FieldOptionValues`.`id_new`;

-- update created_user_id in staff_position_grades with the original value
UPDATE `staff_position_grades` AS `StaffPositionGrades`
INNER JOIN `field_option_values` AS `FieldOptionValues` ON `FieldOptionValues`.`id_new` = `StaffPositionGrades`.`id`
SET `StaffPositionGrades`.`created_user_id` = `FieldOptionValues`.`created_user_id`;
-- End

-- Alter table - add status_id
ALTER TABLE `institution_positions` ADD `status_id` INT(11) NOT NULL COMMENT 'links to workflow_steps.id' AFTER `id`;

-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `filter`, `created_user_id`, `created`) VALUES
('Institutions > Positions', 'Institution.InstitutionPositions', NULL, 1, NOW());

-- Pre-insert workflow for Institution > Positions
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Institution.InstitutionPositions';
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('POSITION-1001', 'Positions', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'POSITION-1001' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_steps` (`name`, `stage`, `is_editable`, `is_removable`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 0, 1, 1, @workflowId, 1, NOW()),
('Pending For Approval', 1, 0, 0, @workflowId, 1, NOW()),
('Closed', 2, 0, 0, @workflowId, 1, NOW()),
('Active', NULL, 0, 0, @workflowId, 1, NOW()),
('Pending For Deactivation', NULL, 0, 0, @workflowId, 1, NOW()),
('Inactive', NULL, 0, 0, @workflowId, 1, NOW());

SET @openStepId := 0;
SET @approvalStepId := 0;
SET @closedStepId := 0;
SET @activeStepId := 0;
SET @deactivateStepId := 0;
SET @inactiveStepId := 0;
SELECT `id` INTO @openStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Open' AND `stage` = 0;
SELECT `id` INTO @approvalStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending For Approval' AND `stage` = 1;
SELECT `id` INTO @closedStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Closed' AND `stage` = 2;
SELECT `id` INTO @activeStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Active';
SELECT `id` INTO @deactivateStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending For Deactivation';
SELECT `id` INTO @inactiveStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Inactive';

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
('Submit For Deactivation', NULL, 1, @deactivateStepId, '', 1, @activeStepId, 1, NOW()),
('Approve', 0, 1, @inactiveStepId, '', 0, @deactivateStepId, 1, NOW()),
('Reject', 1, 1, @activeStepId, '', 1, @deactivateStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @inactiveStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @inactiveStepId, 1, NOW()),
('Reactivate', NULL, 1, @approvalStepId, '', 1, @inactiveStepId, 1, NOW());

INSERT INTO `workflow_statuses` (`code`, `name`, `is_editable`, `is_removable`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('ACTIVE', 'Active', 0, 0, @modelId, 1, NOW()),
('INACTIVE', 'Inactive', 0, 0, @modelId, 1, NOW());

SET @activeId := 0;
SET @inactiveId := 0;
SELECT `id` INTO @activeId FROM `workflow_statuses` WHERE `code` = 'ACTIVE' AND `workflow_model_id` = @modelId;
SELECT `id` INTO @inactiveId FROM `workflow_statuses` WHERE `code` = 'INACTIVE' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_statuses_steps` (`id`, `workflow_status_id`, `workflow_step_id`) VALUES
(uuid(), @activeId, @activeStepId),
(uuid(), @inactiveId, @inactiveStepId);
-- End Pre-insert

-- Update status_id
UPDATE `institution_positions` SET `status_id` = @activeStepId WHERE `status` = 1;
UPDATE `institution_positions` SET `status_id` = @inactiveStepId WHERE `status` = 0;
ALTER TABLE `institution_positions` DROP `status`;

-- Pre-insert workflow_records
INSERT INTO `workflow_records` (`model_reference`, `workflow_model_id`, `workflow_step_id`, `created_user_id`, `created`)
SELECT `id`, @modelId, `status_id`, 1, NOW() FROM `institution_positions` WHERE `status_id` <> 0 ORDER BY `id`;

-- Pre-insert workflow_transitions
-- Open to Active
INSERT INTO `workflow_transitions` (`prev_workflow_step_name`, `workflow_step_name`, `workflow_action_name`, `workflow_record_id`, `created_user_id`, `created`)
SELECT 'Open', 'Active', 'Administration - Initial Setup', `id`, 1, NOW() FROM `workflow_records` WHERE `workflow_model_id` = @modelId AND `workflow_step_id` = @activeStepId ORDER BY `model_reference`;

-- Open to Inactive
INSERT INTO `workflow_transitions` (`prev_workflow_step_name`, `workflow_step_name`, `workflow_action_name`, `workflow_record_id`, `created_user_id`, `created`)
SELECT 'Open', 'Inactive', 'Administration - Initial Setup', `id`, 1, NOW() FROM `workflow_records` WHERE `workflow_model_id` = @modelId AND `workflow_step_id` = @inactiveStepId ORDER BY `model_reference`;
