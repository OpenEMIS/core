-- workflow_models
DELETE FROM `workflow_models` WHERE `model` = 'Institution.InstitutionSurveys';

-- workflow_actions
ALTER TABLE `workflow_actions` DROP `workflow_event_id`;

-- Drop new table - workflow_events
DROP TABLE IF EXISTS `workflow_events`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1352';
