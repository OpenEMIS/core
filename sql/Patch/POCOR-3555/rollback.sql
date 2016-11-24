-- security_functions
DELETE FROM `security_functions` WHERE `id` = 1049;
DELETE FROM `security_functions` WHERE `id` = 1050;

UPDATE `security_functions` SET `order` = '1031' WHERE `id` = '1029';
UPDATE `security_functions` SET `order` = '1032' WHERE `id` = '1030';
UPDATE `security_functions` SET `order` = '1033' WHERE `id` = '1031';
UPDATE `security_functions` SET `order` = '1034' WHERE `id` = '1032';
UPDATE `security_functions` SET `order` = '1035' WHERE `id` = '1033';
UPDATE `security_functions` SET `order` = '1037' WHERE `id` = '1035';
UPDATE `security_functions` SET `order` = '1038' WHERE `id` = '1036';
UPDATE `security_functions` SET `order` = '1040' WHERE `id` = '1038';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3555';
