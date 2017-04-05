-- institution_lands
DROP TABLE IF EXISTS `institution_lands`;

-- institution_buildings
DROP TABLE IF EXISTS `institution_buildings`;

-- institution_floors
DROP TABLE IF EXISTS `institution_floors`;

-- land_types
DROP TABLE IF EXISTS `land_types`;

-- building_types
DROP TABLE IF EXISTS `building_types`;

-- floor_types
DROP TABLE IF EXISTS `floor_types`;

-- land_custom_field_values
DROP TABLE IF EXISTS `land_custom_field_values`;

-- building_custom_field_values
DROP TABLE IF EXISTS `building_custom_field_values`;

-- floor_custom_field_values
DROP TABLE IF EXISTS `floor_custom_field_values`;

-- room_statuses
ALTER TABLE `z_3853_room_statuses`
RENAME TO `room_statuses`;

DROP TABLE IF EXISTS `infrastructure_statuses`;

-- infrastructure_custom_forms
DROP TABLE infrastructure_custom_forms;

ALTER TABLE `z_3853_infrastructure_custom_forms`
RENAME TO `infrastructure_custom_forms`;

-- institution_rooms
ALTER TABLE `institution_rooms`
CHANGE COLUMN `institution_floor_id` `institution_infrastructure_id` INT(11) NOT NULL,
CHANGE COLUMN `institution_id` `institution_id` INT(11) NOT NULL,
CHANGE COLUMN `academic_period_id` `academic_period_id` INT(11) NOT NULL,
CHANGE COLUMN `room_type_id` `room_type_id` INT(11) NOT NULL,
CHANGE COLUMN `infrastructure_condition_id` `infrastructure_condition_id` INT(11) NOT NULL;

-- institution_infrastructures
ALTER TABLE `z_3853_institution_infrastructures`
RENAME TO `institution_infrastructures`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3853';