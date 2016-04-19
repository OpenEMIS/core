--
-- POCOR-2450
--

DROP TABLE IF EXISTS `custom_field_types`;
RENAME TABLE `z_2450_custom_field_types` TO `custom_field_types`;

DROP TABLE IF EXISTS `custom_modules`;
RENAME TABLE `z_2450_custom_modules` TO `custom_modules`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2450';
