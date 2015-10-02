-- workflow_models
DELETE FROM `workflow_models` WHERE `model` = 'Institution.InstitutionSurveys';

-- workflow_steps
ALTER TABLE `workflow_steps` DROP `is_removable`, DROP `is_editable`;

-- workflow_actions
ALTER TABLE `workflow_actions` DROP `event_key`;

-- workflow_records
ALTER TABLE workflow_records DROP INDEX `model_reference`, DROP INDEX `workflow_model_id`, DROP INDEX `workflow_step_id`;

-- labels
DELETE FROM `labels` WHERE `module` = 'WorkflowSteps' AND `field` = 'is_editable';
DELETE FROM `labels` WHERE `module` = 'WorkflowSteps' AND `field` = 'is_removable';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1352';
