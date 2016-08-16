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

INSERT INTO `infrastructure_custom_forms` (`name`, `description`, `custom_module_id`, `created_user_id`, `created`) VALUES
('Infrastructure - Apply To All', '', (SELECT `id` FROM `custom_modules` WHERE `model` = 'Institution.InstitutionInfrastructures'), 1, '0000-00-00 00:00:00'),
('Room - Apply To All', '', (SELECT `id` FROM `custom_modules` WHERE `model` = 'Institution.InstitutionRooms'), 1, '0000-00-00 00:00:00');

SET @infrastructureFormId := 0;
SET @roomFormId := 0;
SELECT `id` INTO @infrastructureFormId FROM `infrastructure_custom_forms` WHERE `name` = 'Infrastructure - Apply To All';
SELECT `id` INTO @roomFormId FROM `infrastructure_custom_forms` WHERE `name` = 'Room - Apply To All';

INSERT INTO `infrastructure_custom_forms_filters` (`id`, `infrastructure_custom_form_id`, `infrastructure_custom_filter_id`) VALUES
('9a09bce6-6391-11e6-8c3d-525400b263eb', @infrastructureFormId, 0),
('a617cc41-6391-11e6-8c3d-525400b263eb', @roomFormId, 0);

-- institution_infrastructures
INSERT INTO `institution_infrastructures` (`id`, `code`, `name`, `year_acquired`, `year_disposed`, `comment`, `size`, `parent_id`, `institution_id`, `infrastructure_level_id`, `infrastructure_type_id`, `infrastructure_ownership_id`, `infrastructure_condition_id`, `created_user_id`, `created`)
SELECT `Infrastructures`.`id`, `Infrastructures`.`code`, `Infrastructures`.`name`, `Infrastructures`.`year_acquired`, `Infrastructures`.`year_disposed`, `Infrastructures`.`comment`, `Infrastructures`.`size`, `Infrastructures`.`parent_id`, `Infrastructures`.`institution_id`, `Infrastructures`.`infrastructure_level_id`, `Infrastructures`.`infrastructure_level_id`, `Infrastructures`.`infrastructure_ownership_id`, `Infrastructures`.`infrastructure_condition_id`, `Infrastructures`.`created_user_id`, `Infrastructures`.`created`
FROM `z_3017_institution_infrastructures` `Infrastructures` INNER JOIN `infrastructure_types` `Types` ON `Types`.`id` = `Infrastructures`.`infrastructure_level_id`;

-- institution_rooms
SET @statusId := 0;
SELECT `id` INTO @statusId FROM `room_statuses` WHERE `code` = 'IN_USE';
SET @periodId := 4;

INSERT INTO `institution_rooms` (`id`, `code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, `room_status_id`, `institution_infrastructure_id`, `institution_id`, `academic_period_id`, `room_type_id`, `infrastructure_condition_id`, `created_user_id`, `created`)
SELECT `Rooms`.`id`, `Rooms`.`code`, `Rooms`.`name`, `AcademicPeriods`.`start_date`, `AcademicPeriods`.`start_year`, `AcademicPeriods`.`end_date`, `AcademicPeriods`.`end_year`, @statusId, `Rooms`.`parent_id`, `Rooms`.`institution_id`, `AcademicPeriods`.`id`, `Rooms`.`infrastructure_level_id`, `Rooms`.`infrastructure_condition_id`, `Rooms`.`created_user_id`, `Rooms`.`created`
FROM `z_3017_institution_infrastructures` `Rooms` INNER JOIN `room_types` `RoomTypes` ON `RoomTypes`.`id` = `Rooms`.`infrastructure_level_id` LEFT JOIN `academic_periods` `AcademicPeriods` ON `AcademicPeriods`.`id` = @periodId;

-- infrastructure_custom_field_values
INSERT INTO `infrastructure_custom_field_values` SELECT `z_3017_infrastructure_custom_field_values`.* FROM `z_3017_infrastructure_custom_field_values` INNER JOIN `institution_infrastructures` ON `institution_infrastructures`.`id` = `institution_infrastructure_id`;

INSERT INTO `room_custom_field_values` (`id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_room_id`, `created_user_id`, `created`)
SELECT `FieldValues`.`id`, `FieldValues`.`text_value`, `FieldValues`.`number_value`, `FieldValues`.`textarea_value`, `FieldValues`.`date_value`, `FieldValues`.`time_value`, `FieldValues`.`file`, `FieldValues`.`infrastructure_custom_field_id`, `FieldValues`.`institution_infrastructure_id`, `FieldValues`.`created_user_id`, `FieldValues`.`created` FROM `z_3017_infrastructure_custom_field_values` `FieldValues` INNER JOIN `institution_rooms` `Rooms` ON `Rooms`.`id` = `FieldValues`.`institution_infrastructure_id`;

-- copy from 2014/2015 to 2015/2016
SET @statusId := 0;
SELECT `id` INTO @statusId FROM `room_statuses` WHERE `code` = 'IN_USE';
SET @copyFrom := 4;
SET @copyTo := 6;

INSERT INTO `institution_rooms` (`code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, `room_status_id`, `institution_infrastructure_id`, `institution_id`, `academic_period_id`, `room_type_id`, `infrastructure_condition_id`, `previous_room_id`, `created_user_id`, `created`)
SELECT `Rooms`.`code`, `Rooms`.`name`, `AcademicPeriods`.`start_date`, `AcademicPeriods`.`start_year`, `AcademicPeriods`.`end_date`, `AcademicPeriods`.`end_year`, `Rooms`.`room_status_id`, `Rooms`.`institution_infrastructure_id`, `Rooms`.`institution_id`, @copyTo, `Rooms`.`room_type_id`, `Rooms`.`infrastructure_condition_id`, `Rooms`.`id`, `Rooms`.`created_user_id`, NOW()
FROM `institution_rooms` `Rooms`
LEFT JOIN `academic_periods` `AcademicPeriods` ON `AcademicPeriods`.`id` = @copyTo WHERE `academic_period_id` = @copyFrom AND `room_status_id` = @statusId;

INSERT INTO `room_custom_field_values` (`id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_room_id`, `created_user_id`, `created`)
SELECT uuid(), `CustomFieldValues`.`text_value`, `CustomFieldValues`.`number_value`, `CustomFieldValues`.`textarea_value`, `CustomFieldValues`.`date_value`, `CustomFieldValues`.`time_value`, `CustomFieldValues`.`file`, `CustomFieldValues`.`infrastructure_custom_field_id`, `CurrentRooms`.`id`, `CustomFieldValues`.`created_user_id`, NOW()
FROM `room_custom_field_values` AS `CustomFieldValues`
INNER JOIN `institution_rooms` AS `PreviousRooms` ON `CustomFieldValues`.`institution_room_id` = `PreviousRooms`.`id` AND `PreviousRooms`.`academic_period_id` = @copyFrom AND `PreviousRooms`.`room_status_id` = @statusId
INNER JOIN `institution_rooms` AS `CurrentRooms` ON `CurrentRooms`.`previous_room_id` = `PreviousRooms`.`id` AND `CurrentRooms`.`academic_period_id` = @copyTo AND `CurrentRooms`.`room_status_id` = @statusId;
