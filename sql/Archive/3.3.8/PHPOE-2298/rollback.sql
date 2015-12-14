-- security_functions
DELETE FROM `security_functions` WHERE `id` = 1025;

INSERT INTO `security_functions` SELECT * FROM `z_2298_security_functions`;

DROP TABLE `z_2298_security_functions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2298';