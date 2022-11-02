-- code here
ALTER TABLE `institution_student_admission` 
	DROP INDEX `institution_class_id`,
	DROP COLUMN `institution_class_id`;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2820';