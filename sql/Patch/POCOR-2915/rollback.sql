-- code here
ALTER TABLE `institution_class_students` DROP INDEX `student_id`;
ALTER TABLE `institution_class_students` DROP INDEX `institution_class_id`;
ALTER TABLE `institution_class_students` DROP INDEX `education_grade_id`;
	


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2915';