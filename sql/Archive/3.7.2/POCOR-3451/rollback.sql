-- restore field_options tables
RENAME TABLE `z_3451_field_options` TO `field_options`;
RENAME TABLE `z_3451_field_option_values` TO `field_option_values`;

-- Drop tables
DROP TABLE IF EXISTS `institution_visit_requests`;

-- Restore tables
DROP TABLE IF EXISTS `workflow_models`;
RENAME TABLE `z_3451_workflow_models` TO `workflow_models`;

-- delete pre-insert workflow
DELETE FROM `workflow_models` WHERE `id` = 9;

DELETE FROM `workflows` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflows`.`workflow_model_id`);
DELETE FROM `workflows_filters` WHERE NOT EXISTS (SELECT 1 FROM `workflows` WHERE `workflows`.`id` = `workflows_filters`.`workflow_id`);
DELETE FROM `workflow_steps` WHERE NOT EXISTS (SELECT 1 FROM `workflows` WHERE `workflows`.`id` = `workflow_steps`.`workflow_id`);
DELETE FROM `workflow_actions` WHERE NOT EXISTS (SELECT 1 FROM `workflow_steps` WHERE `workflow_steps`.`id` = `workflow_actions`.`workflow_step_id`);

DELETE FROM `workflow_statuses` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflow_statuses`.`workflow_model_id`);
DELETE FROM `workflow_statuses_steps` WHERE NOT EXISTS (SELECT 1 FROM `workflow_statuses` WHERE `workflow_statuses`.`id` = `workflow_statuses_steps`.`workflow_status_id`);
DELETE FROM `workflow_transitions` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflow_transitions`.`workflow_model_id`);

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 1048;

UPDATE `security_functions` SET `order` = 1029 WHERE `id` = 1027;

-- labels
DELETE FROM `labels` WHERE `id` = '8077f98a-9b4f-11e6-8f28-525400b263eb';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3451';
