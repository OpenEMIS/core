-- absence_types
DROP TABLE IF EXISTS `absence_types`;

-- institution_staff_absences
ALTER TABLE `institution_staff_absences` 
DROP COLUMN `absence_type_id`,
DROP INDEX `absence_type_id` ;

-- institution_student_absences
ALTER TABLE `institution_student_absences` 
DROP COLUMN `absence_type_id`,
DROP INDEX `absence_type_id` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2562';
