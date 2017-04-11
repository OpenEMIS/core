-- security_functions
UPDATE `security_functions` SET `_view` = 'Surveys.index|Surveys.view', `_add` = NULL, `_edit` = 'Surveys.edit', `_delete` = 'Surveys.remove', `_execute` = NULL
WHERE `id` = 1024;

UPDATE `security_functions` SET `_view` = 'Surveys.index|Surveys.view', `_add` = NULL, `_edit` = NULL, `_delete` = 'Surveys.remove', `_execute` = NULL
WHERE `id` = 1025;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1933';
