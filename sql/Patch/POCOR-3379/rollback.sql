-- security_functions
DELETE FROM `security_functions` WHERE `id` = 1047;

UPDATE `security_functions` SET `order` = '1001' WHERE `id` = '1002';

UPDATE `security_functions` SET `order` = '1002' WHERE `id` = '1001';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3379';
