-- custom_field_values
DROP TABLE IF EXISTS `custom_field_values`;
RENAME TABLE `z_3516_custom_field_values` TO `custom_field_values`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3516';
