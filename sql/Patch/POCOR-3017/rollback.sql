-- Restore tables
DROP TABLE IF EXISTS `infrastructure_levels`;
RENAME TABLE `z_3017_infrastructure_levels` TO `infrastructure_levels`;

DROP TABLE IF EXISTS `infrastructure_types`;
RENAME TABLE `z_3017_infrastructure_types` TO `infrastructure_types`;

DROP TABLE IF EXISTS `room_types`;
DROP TABLE IF EXISTS `institution_rooms`;

-- custom field
RENAME TABLE `z_3017_infrastructure_custom_table_columns` TO `infrastructure_custom_table_columns`;
RENAME TABLE `z_3017_infrastructure_custom_table_rows` TO `infrastructure_custom_table_rows`;
RENAME TABLE `z_3017_infrastructure_custom_table_cells` TO `infrastructure_custom_table_cells`;
DROP TABLE IF EXISTS `room_custom_field_values`;

-- custom_modules
DROP TABLE IF EXISTS `custom_modules`;
RENAME TABLE `z_3017_custom_modules` TO `custom_modules`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3017';
