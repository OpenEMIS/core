-- POCOR-3449
-- drop table
DROP TABLE `staff_training_applications`;

-- delete workflow_models
SET @modelId := 10;
DELETE FROM `workflow_models` WHERE `id` = @modelId;

-- delete workflows
SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'TRN-5001' AND `workflow_model_id` = @modelId;
DELETE FROM `workflows` WHERE `id` = @workflowId;

-- delete workflow_actions
DELETE FROM `workflow_actions` WHERE `workflow_actions`.`workflow_step_id` IN (
  SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = @workflowId
);

-- delete workflow_steps_roles
DELETE FROM `workflow_steps_roles` WHERE `workflow_steps_roles`.`workflow_step_id` IN (
  SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = @workflowId
);

-- delete workflow_statuses_steps
DELETE FROM `workflow_statuses_steps` WHERE `workflow_status_id` IN (
  SELECT `id` FROM `workflow_statuses` WHERE `workflow_model_id` = @modelId
);

-- delete workflow_steps
DELETE FROM `workflow_steps` WHERE `workflow_id` = @workflowId;

-- delete workflow_statuses
DELETE FROM `workflow_statuses` WHERE `workflow_model_id` = @modelId;

-- delete workflow_transitions
DELETE FROM `workflow_transitions` WHERE `workflow_model_id` = @modelId;

-- delete security_functions
DELETE FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Applications';
DELETE FROM `security_functions` WHERE `controller` = 'Trainings' AND `name` = 'Applications';

-- revert staff training security_functions
UPDATE `security_functions`
SET `controller` = 'Staff', `_view` = 'TrainingNeeds.index|TrainingNeeds.view', `_edit` = 'TrainingNeeds.edit', `_add` = 'TrainingNeeds.add', `_delete` = 'TrainingNeeds.remove'
WHERE `name` = 'Needs' AND `category` = 'Staff - Training';

UPDATE `security_functions`
SET `controller` = 'Staff', `_view` = 'TrainingResults.index|TrainingResults.view'
WHERE `name` = 'Results' AND `category` = 'Staff - Training';

-- training_sessions_trainees
ALTER TABLE `training_sessions_trainees`
DROP COLUMN `status`,
CHANGE COLUMN `id` `id` CHAR(36) NOT NULL ,
DROP PRIMARY KEY,
ADD PRIMARY KEY (`id`),
DROP INDEX `id_UNIQUE`;

-- workflow_models
UPDATE `workflow_models` SET `model`='Staff.TrainingNeeds' WHERE `model`='Institution.StaffTrainingNeeds';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3449';


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

UPDATE `security_functions` SET `order` = 1029 WHERE `id` = 1027;

-- labels
DELETE FROM `labels` WHERE `id` = '8077f98a-9b4f-11e6-8f28-525400b263eb';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3451';


-- 3.7.1.2
UPDATE config_items SET value = '3.7.1.2' WHERE code = 'db_version';
