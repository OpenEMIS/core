-- education_grades_subjects
ALTER TABLE `education_grades_subjects` CHANGE `hours_required` `hours_required` INT(5) NOT NULL;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-4025';
