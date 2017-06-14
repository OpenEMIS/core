-- reports
DROP TABLE IF EXISTS `reports`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-2470';
