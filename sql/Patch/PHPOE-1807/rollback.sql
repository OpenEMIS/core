-- security_functions
UPDATE `security_functions` SET `_execute` = NULL WHERE `id` = 5027;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1807';
