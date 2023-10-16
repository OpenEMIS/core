-- `institution_genders`
DROP TABLE IF EXISTS `education_grades`;
RENAME TABLE `z_3905_education_grades` TO `education_grades`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3905';
