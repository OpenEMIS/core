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
INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('126', 'Validate Area Level', 'institution_validate_area_level_id', 'Institution', 'Validate Area Level', '2', '1', '1', '1', 'Dropdown', 'database:Area.AreaLevels', NULL, NULL, '1', '2017-03-08 00:00:00');

INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('127', 'Validate Area Administrative Level', 'institution_validate_area_administrative_level_id', 'Institution', 'Validate Area Administrative Level', '2', '1', '1', '1', 'Dropdown', 'database:Area.AreaAdministrativeLevels', NULL, NULL, '1', '2017-03-08 00:00:00');


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
