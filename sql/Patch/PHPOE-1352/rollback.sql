-- workflow_models
DELETE FROM `workflow_models` WHERE `model` = 'Institution.InstitutionSurveys';

-- workflow_actions
ALTER TABLE `workflow_actions` DROP `event_key`;

-- workflow_records
ALTER TABLE workflow_records DROP INDEX `model_reference`, DROP INDEX `workflow_model_id`, DROP INDEX `workflow_step_id`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1352';
