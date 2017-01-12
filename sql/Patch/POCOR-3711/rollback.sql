UPDATE `security_functions` SET `order` = `order` - 1 WHERE `id` < 2000 and `order` > 1016;
UPDATE `security_functions` SET `order` = 1017 WHERE `id` = 1016;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3711';
