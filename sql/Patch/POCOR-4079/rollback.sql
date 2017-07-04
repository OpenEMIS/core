-- education_subjects_field_of_studies
DROP TABLE IF EXISTS `education_subjects_field_of_studies`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-4079';
