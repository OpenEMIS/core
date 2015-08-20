-- revert security_functions
UPDATE `security_functions` SET `_view` = 'Surveys.index', `_add` = NULL, `_edit` = NULL, `_delete` = 'Surveys.edit|Surveys.remove', `_execute` = NULL
WHERE `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Surveys' AND `name` = 'New';

UPDATE `security_functions` SET `_view` = 'Surveys.index', `_add` = NULL, `_edit` = NULL, `_delete` = 'Surveys.view|Surveys.remove', `_execute` = NULL
WHERE `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Surveys' AND `name` = 'Completed';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1657';
