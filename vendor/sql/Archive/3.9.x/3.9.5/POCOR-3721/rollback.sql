-- delete pre-insert workflow
DELETE FROM `workflow_models` WHERE `id` = 11;

DELETE FROM `workflows` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflows`.`workflow_model_id`);
DELETE FROM `workflows_filters` WHERE NOT EXISTS (SELECT 1 FROM `workflows` WHERE `workflows`.`id` = `workflows_filters`.`workflow_id`);
DELETE FROM `workflow_steps` WHERE NOT EXISTS (SELECT 1 FROM `workflows` WHERE `workflows`.`id` = `workflow_steps`.`workflow_id`);
DELETE FROM `workflow_actions` WHERE NOT EXISTS (SELECT 1 FROM `workflow_steps` WHERE `workflow_steps`.`id` = `workflow_actions`.`workflow_step_id`);

DELETE FROM `workflow_statuses` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflow_statuses`.`workflow_model_id`);
DELETE FROM `workflow_statuses_steps` WHERE NOT EXISTS (SELECT 1 FROM `workflow_statuses` WHERE `workflow_statuses`.`id` = `workflow_statuses_steps`.`workflow_status_id`);
DELETE FROM `workflow_transitions` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflow_transitions`.`workflow_model_id`);

-- license_types
DELETE FROM `license_types` WHERE `international_code` = 'TEACHING_LICENSE_PROVISIONAL';
DELETE FROM `license_types` WHERE `international_code` = 'TEACHING_LICENSE_FULL';

-- license_classifications
DROP TABLE IF EXISTS `license_classifications`;

-- staff_licenses_classifications
DROP TABLE IF EXISTS `staff_licenses_classifications`;

-- staff_licenses
DROP TABLE IF EXISTS `staff_licenses`;
RENAME TABLE `z_3721_staff_licenses` TO `staff_licenses`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3721';
