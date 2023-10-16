DELETE FROM labels WHERE module = 'StudentBehaviours' and field = 'openemis_no' and en = 'OpenEMIS ID';
DELETE FROM labels WHERE module = 'StaffBehaviours' and field = 'openemis_no' and en = 'OpenEMIS ID';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1592';

-- revert security_functions
UPDATE `security_functions` SET `_view` = 'Surveys.index', `_add` = NULL, `_edit` = NULL, `_delete` = 'Surveys.edit|Surveys.remove', `_execute` = NULL
WHERE `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Surveys' AND `name` = 'New';

UPDATE `security_functions` SET `_view` = 'Surveys.index', `_add` = NULL, `_edit` = NULL, `_delete` = 'Surveys.view|Surveys.remove', `_execute` = NULL
WHERE `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Surveys' AND `name` = 'Completed';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1657';

-- labels
DELETE FROM `labels` WHERE `module` = 'StudentPromotion';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1857';

-- restore table
DROP TABLE assessment_item_results;
RENAME TABLE z_1878_assessment_item_results TO assessment_item_results;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1878';

UPDATE `config_items` SET `value` = '3.0.8' WHERE `code` = 'db_version';
