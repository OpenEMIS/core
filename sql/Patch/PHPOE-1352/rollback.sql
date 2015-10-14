-- workflow_models
DELETE FROM `workflow_models` WHERE `model` = 'Institution.InstitutionSurveys';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1352';
