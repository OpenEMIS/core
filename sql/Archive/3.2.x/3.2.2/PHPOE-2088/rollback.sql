-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Results.index' WHERE `id` = 1015;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2088';
