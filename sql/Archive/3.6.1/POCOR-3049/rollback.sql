-- security_functions
DELETE FROM `security_functions` WHERE `id`='5043';

UPDATE `security_functions` SET `order` = `order`-1 WHERE `order` >= 5029 AND `order` <= 5043;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3049';