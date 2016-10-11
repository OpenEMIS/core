-- code here
ALTER TABLE `institution_subject_students` DROP INDEX `institution_id`;
ALTER TABLE `institution_subject_students` DROP INDEX `student_id`; 
ALTER TABLE `institution_subject_students` DROP INDEX `institution_class_id`; 
ALTER TABLE `institution_subject_students` DROP INDEX `academic_period_id`;
ALTER TABLE `institution_subject_students` DROP INDEX `education_subject_id`;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2967';