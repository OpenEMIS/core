-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2392', NOW());

-- Backup table
CREATE TABLE `z_2392_institution_infrastructures` LIKE  `institution_infrastructures`;
INSERT INTO `z_2392_institution_infrastructures` SELECT * FROM `institution_infrastructures` WHERE 1;

-- Start: infrastructure_ownerships
DROP TABLE IF EXISTS `infrastructure_ownerships`;
CREATE TABLE IF NOT EXISTS `infrastructure_ownerships` (
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
UPDATE `field_options` SET `params` = '{"model":"FieldOption.InfrastructureOwnerships"}' WHERE `code` = 'InfrastructureOwnerships';

-- move out infrastructure_ownerships from field_option_values and start with new id
INSERT INTO `infrastructure_ownerships` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`,`created`)
SELECT `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `id`, `created` FROM `field_option_values`
WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'InfrastructureOwnerships');
UPDATE `field_option_values` SET `visible` = 0 WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'InfrastructureOwnerships');

-- update new id back to field_option_values
UPDATE `field_option_values` AS `FieldOptionValues`
INNER JOIN `infrastructure_ownerships` AS `InfrastructureOwnerships` ON `InfrastructureOwnerships`.`created_user_id` = `FieldOptionValues`.`id`
SET `FieldOptionValues`.`id_new` = `InfrastructureOwnerships`.`id`;

-- pacth new id to all hasMany tables
UPDATE `institution_infrastructures` AS `InstitutionInfrastructures`
INNER JOIN `field_option_values` AS `FieldOptionValues` ON `FieldOptionValues`.`id` = `InstitutionInfrastructures`.`infrastructure_ownership_id`
SET `InstitutionInfrastructures`.`infrastructure_ownership_id` = `FieldOptionValues`.`id_new`;

-- update created_user_id in infrastructure_ownerships with the original value
UPDATE `infrastructure_ownerships` AS `InfrastructureOwnerships`
INNER JOIN `field_option_values` AS `FieldOptionValues` ON `FieldOptionValues`.`id_new` = `InfrastructureOwnerships`.`id`
SET `InfrastructureOwnerships`.`created_user_id` = `FieldOptionValues`.`created_user_id`;
-- End

-- Start: infrastructure_conditions
DROP TABLE IF EXISTS `infrastructure_conditions`;
CREATE TABLE IF NOT EXISTS `infrastructure_conditions` (
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
UPDATE `field_options` SET `params` = '{"model":"FieldOption.InfrastructureConditions"}' WHERE `code` = 'InfrastructureConditions';

-- move out infrastructure_conditions from field_option_values and start with new id
INSERT INTO `infrastructure_conditions` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`,`created`)
SELECT `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `id`, `created` FROM `field_option_values`
WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'InfrastructureConditions');
UPDATE `field_option_values` SET `visible` = 0 WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'InfrastructureConditions');

-- update new id back to field_option_values
UPDATE `field_option_values` AS `FieldOptionValues`
INNER JOIN `infrastructure_conditions` AS `InfrastructureConditions` ON `InfrastructureConditions`.`created_user_id` = `FieldOptionValues`.`id`
SET `FieldOptionValues`.`id_new` = `InfrastructureConditions`.`id`;

-- pacth new id to all hasMany tables
UPDATE `institution_infrastructures` AS `InstitutionInfrastructures`
INNER JOIN `field_option_values` AS `FieldOptionValues` ON `FieldOptionValues`.`id` = `InstitutionInfrastructures`.`infrastructure_condition_id`
SET `InstitutionInfrastructures`.`infrastructure_condition_id` = `FieldOptionValues`.`id_new`;

-- update created_user_id in infrastructure_conditions with the original value
UPDATE `infrastructure_conditions` AS `InfrastructureConditions`
INNER JOIN `field_option_values` AS `FieldOptionValues` ON `FieldOptionValues`.`id_new` = `InfrastructureConditions`.`id`
SET `InfrastructureConditions`.`created_user_id` = `FieldOptionValues`.`created_user_id`;
-- End

-- institution_infrastructures
ALTER TABLE `institution_infrastructures` ADD `parent_id` INT(11) NULL DEFAULT NULL AFTER `size`;
ALTER TABLE `institution_infrastructures` ADD `lft` INT(11) NULL DEFAULT NULL AFTER `parent_id`;
ALTER TABLE `institution_infrastructures` ADD `rght` INT(11) NULL DEFAULT NULL AFTER `lft`;

-- patch Infrastructure
DROP PROCEDURE IF EXISTS patchInfrastructure;
DELIMITER $$

CREATE PROCEDURE patchInfrastructure()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE levelId, parentId, minId INT(11);
  DECLARE infra_levels CURSOR FOR
		SELECT `InfrastructureLevels`.`id`, `InfrastructureLevels`.`parent_id`
		FROM `infrastructure_levels` AS `InfrastructureLevels`
		WHERE `InfrastructureLevels`.`parent_id` <> 0
		ORDER BY `InfrastructureLevels`.`parent_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN infra_levels;

  read_loop: LOOP
    FETCH infra_levels INTO levelId, parentId;
    IF done THEN
      LEAVE read_loop;
    END IF;

	SELECT MIN(`id`) INTO minId FROM `institution_infrastructures` WHERE `infrastructure_level_id` = parentId;
	UPDATE `institution_infrastructures` SET `parent_id` = minId WHERE `infrastructure_level_id` = levelId;

  END LOOP read_loop;

  CLOSE infra_levels;
END
$$

DELIMITER ;

CALL patchInfrastructure;

DROP PROCEDURE IF EXISTS patchInfrastructure;

--
-- POCOR-2489
--

-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2489', NOW());

-- security_functions
INSERT INTO `security_functions` 
(`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) 
VALUES ('6008', 'Map', 'Map', 'Reports', 'Reports', '-1', 'index', NULL, NULL, NULL, NULL, '6008', '1', '1', NOW());

INSERT INTO `db_patches` VALUES ('POCOR-2515', NOW());

CREATE TABLE z2515_institution_shifts LIKE institution_shifts;
INSERT INTO z2515_institution_shifts SELECT * FROM institution_shifts;

UPDATE institution_shifts SET start_time = STR_TO_DATE(start_time, '%h:%i %p');
UPDATE institution_shifts SET end_time = STR_TO_DATE(end_time, '%h:%i %p');

ALTER TABLE `institution_shifts` CHANGE `start_time` `start_time` TIME NOT NULL, CHANGE `end_time` `end_time` TIME NOT NULL;

UPDATE `labels` SET `field_name` = 'Location' WHERE field = 'location_institution_id' AND module_name = 'Institutions -> Shifts';
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (uuid(), 'InstitutionShifts', 'location', 'Institutions -> Shifts', 'Occupied By', NULL, NULL, '1', NULL, NULL, '1', now());

-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2526', NOW());

-- authentication_type_attributes
ALTER TABLE `authentication_type_attributes` 
RENAME TO `z_2526_authentication_type_attributes`;

CREATE TABLE `authentication_type_attributes` (
  `id` char(36) NOT NULL,
  `authentication_type` varchar(50) NOT NULL,
  `attribute_field` varchar(50) NOT NULL,
  `attribute_name` varchar(50) NOT NULL,
  `value` text,
  `modified` datetime DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `created_user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- config_item_options
INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) VALUES ('authentication_type', 'Saml2', 'Saml2', 4, 1);

-- config_items
CREATE TABLE `z_2526_config_items` LIKE `config_items`;

INSERT INTO `z_2526_config_items` SELECT * FROM `config_items` WHERE `code` = 'authentication_type' AND `type` = 'Authentication';

UPDATE `config_items` SET `value` = 'Local' WHERE `code` = 'authentication_type' AND `type` = 'Authentication';

-- POCOR-2535
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

-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2539', NOW());

-- staff_position_titles
ALTER TABLE `staff_position_titles` 
ADD COLUMN `security_role_id` INT NULL DEFAULT 0 COMMENT '' AFTER `type`;

ALTER TABLE `staff_position_titles` 
CHANGE COLUMN `security_role_id` `security_role_id` INT(11) NOT NULL COMMENT '' ,
ADD INDEX `security_role_id` (`security_role_id`);

-- DB Version
UPDATE config_items SET value = '3.4.13' WHERE code = 'db_version';
