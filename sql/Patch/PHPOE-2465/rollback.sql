UPDATE security_functions SET _execute = '' WHERE id = 1027;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2465';