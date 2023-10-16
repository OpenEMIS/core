-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1933');

-- security_functions
UPDATE `security_functions` SET `_view` = 'Surveys.index|Surveys.view|NewSurveys.index|NewSurveys.view', `_add` = NULL, `_edit` = 'Surveys.edit|NewSurveys.edit', `_delete` = 'Surveys.remove|NewSurveys.remove', `_execute` = NULL
WHERE `id` = 1024;

UPDATE `security_functions` SET `_view` = 'Surveys.index|Surveys.view|CompletedSurveys.index|CompletedSurveys.view', `_add` = NULL, `_edit` = NULL, `_delete` = 'Surveys.remove|CompletedSurveys.remove', `_execute` = NULL
WHERE `id` = 1025;
