-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2435';

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 1038;
