--
-- POCOR-2450
--

DELETE FROM `custom_field_types` WHERE `id`=10;

DROP TABLE IF EXISTS `custom_modules`;
RENAME TABLE `z_2450_custom_modules` TO `custom_modules`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2450';
