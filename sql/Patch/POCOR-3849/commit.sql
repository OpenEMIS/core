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
