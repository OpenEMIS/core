-- security_functions
DELETE FROM `security_functions` WHERE `id` IN (2031, 7051);

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` >= 2018 AND `order` < 3000;

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` >= 7019 AND `order` < 8000;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3644';
