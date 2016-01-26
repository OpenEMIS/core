-- institution_section_students
ALTER TABLE `institution_section_students` 
DROP COLUMN `student_status_id`,
DROP INDEX `student_status_id` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2501';