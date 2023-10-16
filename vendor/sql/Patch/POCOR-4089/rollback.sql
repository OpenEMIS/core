-- education_grades
DROP TABLE IF EXISTS `education_grades`;
RENAME TABLE `z_4089_education_grades` TO `education_grades`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-4089';
