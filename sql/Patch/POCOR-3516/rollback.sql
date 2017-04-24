-- custom_field_types
DROP TABLE IF EXISTS `custom_field_types`;
RENAME TABLE `z_3516_custom_field_types` TO `custom_field_types`;

-- custom_field_values
DROP TABLE IF EXISTS `custom_field_values`;
RENAME TABLE `z_3516_custom_field_values` TO `custom_field_values`;

-- institution_custom_field_values
DROP TABLE IF EXISTS `institution_custom_field_values`;
RENAME TABLE `z_3516_institution_custom_field_values` TO `institution_custom_field_values`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3516';
