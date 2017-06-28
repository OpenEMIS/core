-- reports
DROP TABLE IF EXISTS `reports`;

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 6012;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-2470';
