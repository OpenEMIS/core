-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2376', NOW());

-- institution_student_admission
ALTER TABLE `institution_student_admission` 
ADD COLUMN `new_education_grade_id` INT(11) NULL COMMENT '' AFTER `education_grade_id`,
ADD INDEX `new_education_grade_id` (`new_education_grade_id`);
