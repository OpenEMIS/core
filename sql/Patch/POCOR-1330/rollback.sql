-- education_grades
DROP TABLE IF EXISTS `education_grades`;
RENAME TABLE `z_1330_education_grades` TO `education_grades`;

-- education_absolute_grades
DROP TABLE IF EXISTS `education_absolute_grades`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-1330';
