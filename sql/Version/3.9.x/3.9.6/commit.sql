-- POCOR-3845
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3845', NOW());

-- institution_student_absences
CREATE TABLE `z_3845_institution_student_absences` LIKE `institution_student_absences`;
INSERT INTO `z_3845_institution_student_absences` SELECT * FROM `institution_student_absences`;

UPDATE `institution_student_absences` SET `start_time` = NULL, `end_time` = NULL WHERE `full_day` = 1;


-- POCOR-3846
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3846', NOW());

-- config_product_lists
CREATE TABLE `z_3846_config_product_lists` LIKE `config_product_lists`;

INSERT INTO `z_3846_config_product_lists`
SELECT * FROM `config_product_lists`;

ALTER TABLE `config_product_lists`
DROP COLUMN `deletable`;


-- POCOR-2059
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-2059', NOW());

ALTER TABLE `institution_subject_students` ADD INDEX(`id`);
ALTER TABLE `assessment_item_results` ADD INDEX(`id`);

-- POCOR-3680
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3680', NOW());

-- patch security_group_user_id
CREATE TABLE `z_3680_security_group_users` LIKE `security_group_users`;

INSERT INTO `z_3680_security_group_users`
SELECT * FROM `security_group_users`;

DELETE FROM `security_group_users`
WHERE EXISTS (
    SELECT 1
    FROM `institutions`
    WHERE `security_group_id` = `security_group_users`.`security_group_id`
);

ALTER TABLE `security_group_users`
ADD COLUMN `institution_staff_id` INT NULL COMMENT '' AFTER `created`;

INSERT INTO `security_group_users`
SELECT
    uuid(),
    `Institutions`.`security_group_id` as security_group_id,
    `InstitutionStaff`.`staff_id` as security_user_id,
    `StaffPositionTitles`.`security_role_id` as security_role_id,
    1,
    NOW(),
    `InstitutionStaff`.`id` as institution_staff_id
FROM `institution_staff` `InstitutionStaff`
INNER JOIN `institutions` `Institutions`
    ON `Institutions`.`id` = `InstitutionStaff`.`institution_id`
INNER JOIN `institution_positions` `Positions`
    ON `Positions`.`id` = `InstitutionStaff`.`institution_position_id`
INNER JOIN `staff_position_titles` `StaffPositionTitles`
    ON `StaffPositionTitles`.`id` = `Positions`.`staff_position_title_id`
    AND `StaffPositionTitles`.`security_role_id` <> 0
WHERE `InstitutionStaff`.`staff_status_id` = 1; #assigned staff only

CREATE TABLE `z_3680_institution_staff` LIKE `institution_staff`;
INSERT INTO `z_3680_institution_staff`
SELECT * FROM `institution_staff`;

UPDATE `institution_staff`
SET `security_group_user_id` = NULL;

UPDATE `institution_staff`
INNER JOIN `security_group_users`
    ON `security_group_users`.`institution_staff_id` = `institution_staff`.`id`
SET `institution_staff`.`security_group_user_id` = `security_group_users`.`id`;

ALTER TABLE `security_group_users`
DROP COLUMN `institution_staff_id`;


-- POCOR-3849
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3849', NOW());

-- room_types
CREATE TABLE `z_3849_room_types` LIKE `room_types`;
INSERT INTO `z_3849_room_types`
SELECT * FROM `room_types`;

ALTER TABLE `room_types` ADD `classification` INT(1) NOT NULL DEFAULT 0 COMMENT '0 -> Non-Classroom, 1 -> Classroom' AFTER `default`;

-- data patch
-- 1. update all to classification to 0
UPDATE `room_types` SET `classification` = 0;

-- 2. update classification to 1 where International code = CLASSROOM and room types linked with inst subject.
UPDATE `room_types` AS rt
    LEFT JOIN `institution_rooms`          AS ir ON rt.`id`  = ir.`room_type_id`
    LEFT JOIN `institution_subjects_rooms` AS isr ON ir.`id` = isr.`institution_room_id`
    SET rt.`classification` = 1
    WHERE rt.`international_code` = 'CLASSROOM'
    OR isr.`id` IS NOT NULL;

-- 3. update non-editable classroom type to become editable.
UPDATE `room_types` SET `editable` = 1;

-- 4. set the international and national code to null.
UPDATE `room_types` SET `international_code` = NULL;
UPDATE `room_types` SET `national_code` = NULL;


-- POCOR-3857
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3857', NOW());

-- config_items

#get the first level as initial value
SELECT `level` INTO @HighestAreaLevel
FROM `area_levels`
ORDER BY `level`
LIMIT 1;

INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('126', 'Validate Area Level', 'institution_validate_area_level_id', 'Institution', 'Validate Area Level', @HighestAreaLevel, '1', '1', '1', 'Dropdown', 'database:Area.AreaLevels', NULL, NULL, '1', '2017-03-08 00:00:00');

#get the first level as initial value
SELECT `id` INTO @HighestAreaAdministrativeLevel
FROM area_administrative_levels
ORDER BY `level`
LIMIT 1;

INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('127', 'Validate Area Administrative Level', 'institution_validate_area_administrative_level_id', 'Institution', 'Validate Area Administrative Level', @HighestAreaAdministrativeLevel, '1', '1', '1', 'Dropdown', 'database:Area.AreaAdministrativeLevels', NULL, NULL, '1', '2017-03-08 00:00:00');


-- 3.9.6
UPDATE config_items SET value = '3.9.6' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
