-- POCOR-3450
-- code here
DROP TABLE `staff_appraisals`;
DROP TABLE `staff_appraisal_types`;
DROP TABLE `competencies`;
DROP TABLE `competency_sets`;
DROP TABLE `competency_sets_competencies`;
DROP TABLE `staff_appraisals_competencies`;

-- security_function
DELETE FROM `security_functions` WHERE `id` = 3037;
DELETE FROM `security_functions` WHERE `id` = 7049;
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `id` BETWEEN 3000 AND 4000 AND `order` >= 3025;
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `id` BETWEEN 7000 AND 8000 AND `order` >= 7033;

-- security_role_function
DELETE FROM `security_role_functions` WHERE `security_function_id` = 3037;
DELETE FROM `security_role_functions` WHERE `security_function_id` = 7049;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3450';


-- POCOR-3451
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

UPDATE `security_functions` SET `_execute` = 'Visits.download', `order` = 1029 WHERE `id` = 1027;

-- labels
DELETE FROM `labels` WHERE `id` = '8077f98a-9b4f-11e6-8f28-525400b263eb';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3451';


-- 3.7.1.2
UPDATE config_items SET value = '3.7.1.2' WHERE code = 'db_version';
