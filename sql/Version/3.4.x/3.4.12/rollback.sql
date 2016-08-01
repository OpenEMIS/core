UPDATE `field_options` SET `plugin` = 'Staff' WHERE `field_options`.`code` = 'StaffPositionGrades';
UPDATE `field_options` SET `plugin` = 'Staff' WHERE `field_options`.`code` = 'StaffPositionTitles';

UPDATE `field_options` SET `plugin` = 'Students' WHERE `field_options`.`code` = 'StudentDropoutReasons';

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2232';

--
-- POCOR-2506
--

DROP TABLE IF EXISTS `staff_position_titles`;

UPDATE `field_option_values` as `fov` set `fov`.`visible`=1 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'StaffPositionTitles'); 

DROP TABLE IF EXISTS `institution_positions`;
ALTER TABLE `z_2506_institution_positions` RENAME `institution_positions`;

UPDATE `security_functions` SET `_delete` = 'remove' WHERE `security_functions`.`id` = 5013;

DROP TABLE IF EXISTS `institution_genders`;
UPDATE `field_option_values` as `fov` set `fov`.`visible`=1 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Genders'); 

DROP TABLE IF EXISTS `institution_localities`;
UPDATE `field_option_values` as `fov` set `fov`.`visible`=1 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Localities'); 

DROP TABLE IF EXISTS `institution_ownerships`;
UPDATE `field_option_values` as `fov` set `fov`.`visible`=1 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Ownerships'); 

DROP TABLE IF EXISTS `institution_providers`;
UPDATE `field_option_values` as `fov` set `fov`.`visible`=1 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Providers'); 

DROP TABLE IF EXISTS `institution_sectors`;
UPDATE `field_option_values` as `fov` set `fov`.`visible`=1 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Sectors'); 

DROP TABLE IF EXISTS `institution_statuses`;
UPDATE `field_option_values` as `fov` set `fov`.`visible`=1 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Statuses'); 

DROP TABLE IF EXISTS `institution_types`;
UPDATE `field_option_values` as `fov` set `fov`.`visible`=1 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Types'); 

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2506';

UPDATE security_functions SET _execute = '' WHERE id = 1027;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2465';

--
-- POCOR-2497
--

-- security_functions
UPDATE `security_functions` SET `_view` = 'StaffSubjects.index' WHERE `security_functions`.`id` = 7023;
DELETE FROM `security_functions` WHERE `id` IN ('7045', '7046');

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2497';

-- institution_section_students
UPDATE `institution_section_students`
INNER JOIN `z_2501_institution_section_students` ON `z_2501_institution_section_students`.`id` = `institution_section_students`.`id`
SET `institution_section_students`.`education_grade_id` = `z_2501_institution_section_students`.`education_grade_id`;

DROP TABLE `z_2501_institution_section_students`;

ALTER TABLE `institution_section_students` 
DROP COLUMN `student_status_id`,
DROP INDEX `student_status_id` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2501';

UPDATE config_items SET value = '3.4.11' WHERE code = 'db_version';
