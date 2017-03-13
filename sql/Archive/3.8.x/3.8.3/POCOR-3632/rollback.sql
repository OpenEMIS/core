-- security_functions
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 5058;

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` < 6000 AND `order` > 5010;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3632';
