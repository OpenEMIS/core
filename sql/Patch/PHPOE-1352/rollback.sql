-- workflow_models
DELETE FROM `workflow_models` WHERE `model` = 'Institution.InstitutionSurveys';

-- workflow_actions
ALTER TABLE `workflow_actions` DROP `event_key`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1352';
