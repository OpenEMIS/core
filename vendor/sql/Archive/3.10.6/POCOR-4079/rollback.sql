-- education_subjects_field_of_studies
DROP TABLE IF EXISTS `education_subjects_field_of_studies`;

-- staff_qualifications
DROP TABLE IF EXISTS `staff_qualifications`;
RENAME TABLE `z_4079_staff_qualifications` TO `staff_qualifications`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-4079';
