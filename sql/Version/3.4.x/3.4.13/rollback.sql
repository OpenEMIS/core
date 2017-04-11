-- institution_infrastructures
-- ALTER TABLE `institution_infrastructures` DROP `parent_id`;
-- ALTER TABLE `institution_infrastructures` DROP `lft`;
-- ALTER TABLE `institution_infrastructures` DROP `rght`;

-- revert move out infrastructure_ownerships from field_options
DROP TABLE IF EXISTS `infrastructure_ownerships`;
UPDATE `field_options` SET `params` = NULL WHERE `code` = 'InfrastructureOwnerships';
UPDATE `field_option_values` SET `visible` = 1 WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'InfrastructureOwnerships');

-- revert move out infrastructure_conditions from field_options
DROP TABLE IF EXISTS `infrastructure_conditions`;
UPDATE `field_options` SET `params` = NULL WHERE `code` = 'InfrastructureConditions';
UPDATE `field_option_values` SET `visible` = 1 WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'InfrastructureConditions');

-- Restore table
DROP TABLE `institution_infrastructures`;
RENAME TABLE `z_2392_institution_infrastructures` TO `institution_infrastructures`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2392';

--
-- POCOR-2489
--

-- security_functions
DELETE FROM `security_functions` WHERE  `id` = '6008';

-- db_patches
DELETE FROM `db_patches` WHERE  `issue` = 'POCOR-2489';

DROP TABLE institution_shifts;
RENAME TABLE z2515_institution_shifts TO institution_shifts;

DELETE FROM labels WHERE field = 'location' AND module_name = 'Institutions -> Shifts';
UPDATE `labels` SET `field_name` = 'Institution' WHERE field = 'location_institution_id' AND module_name = 'Institutions -> Shifts';

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2515';

-- authentication_type_attributes
DROP TABLE `authentication_type_attributes`;

ALTER TABLE `z_2526_authentication_type_attributes` 
RENAME TO `authentication_type_attributes`;

-- config_item_options
DELETE FROM `config_item_options` WHERE `option_type` = 'authentication_type' AND `option` = 'Saml2';

UPDATE `config_items` INNER JOIN `z_2526_config_items` ON `z_2526_config_items`.`id` = `config_items`.`id`
SET `config_items`.`value` = `z_2526_config_items`.`value`;

DROP TABLE `z_2526_config_items`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2526';

-- revert move out staff_position_grades from field_options
DROP TABLE IF EXISTS `staff_position_grades`;
UPDATE `field_options` SET `params` = NULL WHERE `code` = 'StaffPositionGrades';
UPDATE `field_option_values` SET `visible` = 1 WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'StaffPositionGrades');

-- delete pre-insert workflow
DELETE FROM `workflow_models` WHERE `model` = 'Institution.InstitutionPositions';
DELETE FROM `workflows` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflows`.`workflow_model_id`);
DELETE FROM `workflow_steps` WHERE NOT EXISTS (SELECT 1 FROM `workflows` WHERE `workflows`.`id` = `workflow_steps`.`workflow_id`);
DELETE FROM `workflow_actions` WHERE NOT EXISTS (SELECT 1 FROM `workflow_steps` WHERE `workflow_steps`.`id` = `workflow_actions`.`workflow_step_id`);

DELETE FROM `workflow_statuses` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflow_statuses`.`workflow_model_id`);
DELETE FROM `workflow_statuses_steps` WHERE NOT EXISTS (SELECT 1 FROM `workflow_statuses` WHERE `workflow_statuses`.`id` = `workflow_statuses_steps`.`workflow_status_id`);

DELETE FROM `workflow_records` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflow_records`.`workflow_model_id`);
DELETE FROM `workflow_transitions` WHERE NOT EXISTS (SELECT 1 FROM `workflow_records` WHERE `workflow_records`.`id` = `workflow_transitions`.`workflow_record_id`);

-- Restore table
DROP TABLE `institution_positions`;
RENAME TABLE `z_2535_institution_positions` TO `institution_positions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2535';

-- staff_position_titles
ALTER TABLE `staff_position_titles` 
DROP COLUMN `security_role_id`,
DROP INDEX `security_role_id` ;

-- db_patches
DELETE `db_patches` WHERE `issue` = 'POCOR-2539';

UPDATE config_items SET value = '3.4.12' WHERE code = 'db_version';
