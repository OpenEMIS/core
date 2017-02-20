-- security_functions
DELETE FROM `security_functions` WHERE `id` IN (2030);

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` >= 2018 AND `order` < 3000;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3644';
