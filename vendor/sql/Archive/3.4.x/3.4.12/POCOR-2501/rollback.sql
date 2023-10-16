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