-- labels
DELETE FROM `labels` WHERE `module` = 'SurveyQuestions' AND `field` = 'name';

-- db_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3535';
