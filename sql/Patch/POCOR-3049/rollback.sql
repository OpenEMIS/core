-- security_functions
DELETE FROM `security_functions` WHERE `id`='5043';

UPDATE `security_functions` SET `order`='5028' WHERE `id`='5028';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3049';