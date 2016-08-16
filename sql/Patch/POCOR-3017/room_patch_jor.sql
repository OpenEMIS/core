-- data patch

-- land type
SET @landId := 1;
SET @order := 1;
INSERT INTO `infrastructure_types` (`id`, `name`, `order`, `visible`, `editable`, `default`, `infrastructure_level_id`, `created_user_id`, `created`)
SELECT `id`, `name`, @order, 1, 1, 0, @landId, 1, '0000-00-00 00:00:00' FROM `z_3017_infrastructure_levels` WHERE `id` IN (SELECT `id` FROM `z_3017_infrastructure_levels` WHERE `parent_id` = 0);

-- building type
SET @buildingId := 2;
SET @order := 2;
INSERT INTO `infrastructure_types` (`id`, `name`, `order`, `visible`, `editable`, `default`, `infrastructure_level_id`, `created_user_id`, `created`)
SELECT `id`, `name`, @order, 1, 1, 0, @buildingId, 1, '0000-00-00 00:00:00' FROM `z_3017_infrastructure_levels` WHERE `id` IN (SELECT `id` FROM `z_3017_infrastructure_levels` WHERE `parent_id` = 1);

-- floor type
SET @floorId := 3;
SET @order := 3;
INSERT INTO `infrastructure_types` (`id`, `name`, `order`, `visible`, `editable`, `default`, `infrastructure_level_id`, `created_user_id`, `created`)
SELECT `id`, `name`, @order, 1, 1, 0, @floorId, 1, '0000-00-00 00:00:00' FROM `z_3017_infrastructure_levels` WHERE `id` IN (SELECT `id` FROM `z_3017_infrastructure_levels` WHERE `parent_id` = 2);

-- room types (set order same as id)
INSERT INTO `room_types` (`id`, `name`, `order`, `visible`, `editable`, `default`, `created_user_id`, `created`)
SELECT `id`, `name`, `id`, 1, 1, 0, 1, '0000-00-00 00:00:00' FROM `z_3017_infrastructure_levels` WHERE `id` IN (SELECT `id` FROM `z_3017_infrastructure_levels` WHERE `parent_id` = 3);

-- infrastructure_custom_forms exclude land, building and floor
UPDATE `infrastructure_custom_forms` SET `custom_module_id` = (SELECT `id` FROM `custom_modules` WHERE `model` = 'Institution.InstitutionRooms') WHERE `id` NOT IN (2, 3);

-- institution_infrastructures
INSERT INTO `institution_infrastructures` (`id`, `code`, `name`, `year_acquired`, `year_disposed`, `comment`, `size`, `parent_id`, `institution_id`, `infrastructure_level_id`, `infrastructure_type_id`, `infrastructure_ownership_id`, `infrastructure_condition_id`, `created_user_id`, `created`)
SELECT `Infrastructures`.`id`, `Infrastructures`.`code`, `Infrastructures`.`name`, `Infrastructures`.`year_acquired`, `Infrastructures`.`year_disposed`, `Infrastructures`.`comment`, `Infrastructures`.`size`, `Infrastructures`.`parent_id`, `Infrastructures`.`institution_id`, `Infrastructures`.`infrastructure_level_id`, `Infrastructures`.`infrastructure_level_id`, `Infrastructures`.`infrastructure_ownership_id`, `Infrastructures`.`infrastructure_condition_id`, `Infrastructures`.`created_user_id`, `Infrastructures`.`created`
FROM `z_3017_institution_infrastructures` `Infrastructures` INNER JOIN `infrastructure_types` `Types` ON `Types`.`id` = `Infrastructures`.`infrastructure_level_id`;

-- institution_rooms
SET @statusId := 0;
SELECT `id` INTO @statusId FROM `room_statuses` WHERE `code` = 'IN_USE';
SET @periodId := 0;

INSERT INTO `institution_rooms` (`id`, `code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, `room_status_id`, `institution_infrastructure_id`, `institution_id`, `academic_period_id`, `room_type_id`, `infrastructure_condition_id`, `created_user_id`, `created`)
SELECT `Rooms`.`id`, `Rooms`.`code`, `Rooms`.`name`, `AcademicPeriods`.`start_date`, `AcademicPeriods`.`start_year`, `AcademicPeriods`.`end_date`, `AcademicPeriods`.`end_year`, @statusId, `Rooms`.`parent_id`, `Rooms`.`institution_id`, `AcademicPeriods`.`id`, `Rooms`.`infrastructure_level_id`, `Rooms`.`infrastructure_condition_id`, `Rooms`.`created_user_id`, `Rooms`.`created`
FROM `z_3017_institution_infrastructures` `Rooms` INNER JOIN `room_types` `RoomTypes` ON `RoomTypes`.`id` = `Rooms`.`infrastructure_level_id` LEFT JOIN `academic_periods` `AcademicPeriods` ON `AcademicPeriods`.`current` = 1;

-- infrastructure_custom_field_values
INSERT INTO `infrastructure_custom_field_values` SELECT `z_3017_infrastructure_custom_field_values`.* FROM `z_3017_infrastructure_custom_field_values` INNER JOIN `institution_infrastructures` ON `institution_infrastructures`.`id` = `institution_infrastructure_id`;

INSERT INTO `room_custom_field_values` (`id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_room_id`, `created_user_id`, `created`)
SELECT `FieldValues`.`id`, `FieldValues`.`text_value`, `FieldValues`.`number_value`, `FieldValues`.`textarea_value`, `FieldValues`.`date_value`, `FieldValues`.`time_value`, `FieldValues`.`file`, `FieldValues`.`infrastructure_custom_field_id`, `FieldValues`.`institution_infrastructure_id`, `FieldValues`.`created_user_id`, `FieldValues`.`created` FROM `z_3017_infrastructure_custom_field_values` `FieldValues` INNER JOIN `institution_rooms` `Rooms` ON `Rooms`.`id` = `FieldValues`.`institution_infrastructure_id`;
