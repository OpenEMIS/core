-- POCOR-3732
-- institutions
DROP TABLE IF EXISTS `institutions`;
RENAME TABLE `z_3732_institutions` TO `institutions`;

-- workflow_transitions
DROP TABLE IF EXISTS `workflow_transitions`;
RENAME TABLE `z_3732_workflow_transitions` TO `workflow_transitions`;

-- staff_behaviours
DROP TABLE IF EXISTS `staff_behaviours`;
RENAME TABLE `z_3732_staff_behaviours` TO `staff_behaviours`;

-- institution_cases
DROP TABLE IF EXISTS `institution_cases`;

-- institution_case_records
DROP TABLE IF EXISTS `institution_case_records`;

-- workflow_rules
DROP TABLE IF EXISTS `workflow_rules`;

-- delete pre-insert workflow
DELETE FROM `workflow_models` WHERE `id` = 12;

DELETE FROM `workflows` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflows`.`workflow_model_id`);
DELETE FROM `workflows_filters` WHERE NOT EXISTS (SELECT 1 FROM `workflows` WHERE `workflows`.`id` = `workflows_filters`.`workflow_id`);
DELETE FROM `workflow_steps` WHERE NOT EXISTS (SELECT 1 FROM `workflows` WHERE `workflows`.`id` = `workflow_steps`.`workflow_id`);
DELETE FROM `workflow_actions` WHERE NOT EXISTS (SELECT 1 FROM `workflow_steps` WHERE `workflow_steps`.`id` = `workflow_actions`.`workflow_step_id`);

DELETE FROM `workflow_statuses` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflow_statuses`.`workflow_model_id`);
DELETE FROM `workflow_statuses_steps` WHERE NOT EXISTS (SELECT 1 FROM `workflow_statuses` WHERE `workflow_statuses`.`id` = `workflow_statuses_steps`.`workflow_status_id`);
DELETE FROM `workflow_transitions` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflow_transitions`.`workflow_model_id`);

-- security_functions
DELETE FROM `security_functions` WHERE `id` IN (1056, 5067);

UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` >= 5043 AND `order` < 6000;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3732';


-- POCOR-3728
-- alerts Table
DELETE FROM `alerts` WHERE `name` = 'LicenseValidity';
DELETE FROM `alerts` WHERE `name` = 'RetirementWarning';
DELETE FROM `alerts` WHERE `name` = 'StaffEmployment';
DELETE FROM `alerts` WHERE `name` = 'StaffLeave';
DELETE FROM `alerts` WHERE `name` = 'StaffType';

UPDATE `alerts` SET `process_name` = 'AttendanceAlert' WHERE `name` = 'Attendance';

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3728';


-- POCOR-3851
ALTER TABLE `contact_types` DROP `validation_pattern`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3851';


-- 3.9.8.1
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.8.1' WHERE code = 'db_version';
