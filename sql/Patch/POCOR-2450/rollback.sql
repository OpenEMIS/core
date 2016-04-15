--
-- POCOR-2450
--

-- backup tables
DROP TABLE IF EXISTS `custom_field_types`;
RENAME TABLE `z_2450_custom_field_types` TO `custom_field_types`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2450';
