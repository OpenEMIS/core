-- education_grades_subjects
DROP TABLE IF EXISTS `education_grades_subjects`;
RENAME TABLE `z_3995_education_grades_subjects` TO `education_grades_subjects`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3995';
