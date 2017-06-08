-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-4025', NOW());

-- education_grades_subjects
ALTER TABLE `education_grades_subjects` CHANGE `hours_required` `hours_required` DECIMAL(5,2) NULL;
