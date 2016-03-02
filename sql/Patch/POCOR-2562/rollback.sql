-- absence_types
DROP TABLE IF EXISTS `absence_types`;

-- institution_staff_absences
ALTER TABLE `institution_staff_absences` 
CHANGE COLUMN `start_time` `start_time` VARCHAR(15) NULL DEFAULT NULL COMMENT '' ,
CHANGE COLUMN `end_time` `end_time` VARCHAR(15) NULL DEFAULT NULL COMMENT '' ;

UPDATE `institution_staff_absences`
INNER JOIN `z_2562_institution_staff_absences` ON `z_2562_institution_staff_absences`.`id` = `institution_staff_absences`.`id`
SET `institution_staff_absences`.`start_time` = `z_2562_institution_staff_absences`.`start_time`, 
`institution_staff_absences`.`end_time` = `z_2562_institution_staff_absences`.`end_time`,
`institution_staff_absences`.`staff_absence_reason_id` = `institution_staff_absences`.`staff_absence_reason_id`;

ALTER TABLE `institution_staff_absences` 
DROP COLUMN `absence_type_id`,
DROP INDEX `absence_type_id` ;

DROP TABLE `z_2562_institution_staff_absences`;

-- institution_student_absences
ALTER TABLE `institution_student_absences` 
CHANGE COLUMN `start_time` `start_time` VARCHAR(15) NULL DEFAULT NULL COMMENT '' ,
CHANGE COLUMN `end_time` `end_time` VARCHAR(15) NULL DEFAULT NULL COMMENT '' ;

UPDATE `institution_student_absences`
INNER JOIN `z_2562_institution_student_absences` ON `z_2562_institution_student_absences`.`id` = `institution_student_absences`.`id`
SET `institution_student_absences`.`start_time` = `z_2562_institution_student_absences`.`start_time`, 
`institution_student_absences`.`end_time` = `z_2562_institution_student_absences`.`end_time`,
`institution_student_absences`.`student_absence_reason_id` = `z_2562_institution_student_absences`.`student_absence_reason_id`;

ALTER TABLE `institution_student_absences` 
DROP COLUMN `absence_type_id`,
DROP INDEX `absence_type_id` ;

DROP TABLE `z_2562_institution_student_absences`;

-- labels
DELETE FROM `labels` WHERE `field` = 'absence_type_id' AND `module` = 'StaffAbsences';
DELETE FROM `labels` WHERE `field` = 'absence_type_id' AND `module` = 'InstitutionStudentAbsences';

-- student_absence_reasons
DROP TABLE `student_absence_reasons`;

-- staff_absence_reasons
DROP TABLE `staff_absence_reasons`;

-- field_option_values
UPDATE `field_option_values` 
INNER JOIN `field_options` 
  ON `field_options`.`id` = `field_option_values`.`field_option_id`
    AND (`field_options`.`code` = 'StaffAbsenceReasons' OR `field_options`.`code` = 'StudentAbsenceReasons')
    AND `field_options`.`plugin` = 'FieldOption'
SET `field_option_values`.`visible` = 1;

UPDATE `field_options` SET `params`=NULL WHERE `code`='StaffAbsenceReasons' AND `plugin` = 'FieldOptions';
UPDATE `field_options` SET `params`=NULL WHERE `code`='StudentAbsenceReasons' AND `plugin` = 'FieldOptions';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2562';
