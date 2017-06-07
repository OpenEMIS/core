-- POCOR-3937
-- import_mapping
DELETE FROM `import_mapping`
WHERE `id` IN (112, 113, 114);

-- security_functions
UPDATE `security_functions` SET `_execute` = NULL WHERE `security_functions`.`id` = 3020;
UPDATE `security_functions` SET `_execute` = NULL WHERE `security_functions`.`id` = 7034;

DELETE FROM `security_functions`
WHERE `id` IN (3039, 7052);

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` > 3022
AND `order` < 4000;

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` > 7038
AND `order` < 8000;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3937';


-- POCOR-3853
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

-- infrastructure_custom_forms_filters
DROP TABLE infrastructure_custom_forms_filters;

ALTER TABLE `z_3853_infrastructure_custom_forms_filters`
RENAME TO `infrastructure_custom_forms_filters`;

-- infrastructure_custom_forms_fields
DROP TABLE infrastructure_custom_forms_fields;

ALTER TABLE `z_3853_infrastructure_custom_forms_fields`
RENAME TO `infrastructure_custom_forms_fields`;

-- infrastructure_custom_field_values
ALTER TABLE `z_3853_infrastructure_custom_field_values`
RENAME TO  `infrastructure_custom_field_values` ;

-- infrastructure_types
ALTER TABLE `z_3853_infrastructure_types`
RENAME TO  `infrastructure_types` ;

-- institution_rooms
ALTER TABLE `institution_rooms`
CHANGE COLUMN `institution_floor_id` `institution_infrastructure_id` INT(11) NOT NULL,
CHANGE COLUMN `institution_id` `institution_id` INT(11) NOT NULL,
CHANGE COLUMN `academic_period_id` `academic_period_id` INT(11) NOT NULL,
CHANGE COLUMN `room_type_id` `room_type_id` INT(11) NOT NULL,
CHANGE COLUMN `infrastructure_condition_id` `infrastructure_condition_id` INT(11) NOT NULL,
CHANGE COLUMN `previous_institution_room_id` `previous_room_id` INT(11) NOT NULL COMMENT 'links to institution_rooms.id';

UPDATE `institution_rooms`
SET `previous_room_id` = 0
WHERE `previous_room_id` = NULL;

-- institution_infrastructures
ALTER TABLE `z_3853_institution_infrastructures`
RENAME TO `institution_infrastructures`;

-- custom_modules
INSERT INTO custom_modules (`id`, `code`, `name`, `model`, `visible`, `parent_id`, `created_user_id`, `created`)
VALUES (4, 'Infrastructure', 'Institution - Infrastructure', 'Institution.InstitutionInfrastructures', 1, 1, 1, '1990-01-01 00:00:00');

DELETE FROM custom_modules WHERE `id` IN (7,8,9);

UPDATE custom_modules
SET id = 7
WHERE id = 10;

-- revert security function
UPDATE `security_functions`
SET
  `_view`='Fields.index|Fields.view|Pages.index|Pages.view|Types.index|Types.view|RoomPages.index|RoomPages.view|RoomTypes.index|RoomTypes.view',
  `_edit`='Fields.edit|Pages.edit|Types.edit|RoomPages.edit|RoomTypes.edit',
  `_add`='Fields.add|Pages.add|Types.add|RoomPages.add|RoomTypes.add',
  `_delete`='Fields.remove|Pages.remove|Types.remove|RoomPages.remove|RoomTypes.remove'
WHERE `id`='5018';

UPDATE `security_functions`
SET
  `_view`='Infrastructures.index|Infrastructures.view|Rooms.index|Rooms.view',
  `_edit`='Infrastructures.edit|Rooms.edit',
  `_add`='Infrastructures.add|Rooms.add',
  `_delete`='Infrastructures.remove|Rooms.remove'
WHERE `id`='1011';

UPDATE `security_functions`
SET
  `_view`='index|view|dashboard',
  `_edit`='edit',
  `_add`='add',
  `_delete`='remove',
  `_execute`='excel'
WHERE `id`='1000';

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3853';


-- POCOR-3905
-- `institution_genders`
DROP TABLE IF EXISTS `education_grades`;
RENAME TABLE `z_3905_education_grades` TO `education_grades`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3905';


-- 3.9.13.1
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.13.1' WHERE code = 'db_version';
