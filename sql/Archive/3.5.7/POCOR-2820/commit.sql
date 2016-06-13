-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2820', NOW());


-- code here
ALTER TABLE `institution_student_admission` 
	ADD COLUMN `institution_class_id` int(11) DEFAULT NULL after `education_grade_id`,
	ADD INDEX `institution_class_id` (`institution_class_id`);