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
DELETE FROM `custom_modules` WHERE `code` = 'Room';
UPDATE `custom_modules` SET `name` = 'Institution - Infrastructure', `filter` = 'Infrastructure.InfrastructureLevels', `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,DATE,TIME,FILE,COORDINATES' WHERE `custom_modules`.`code` = 'Infrastructure';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3017';
