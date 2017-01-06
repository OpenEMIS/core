-- restore institution_subject_students
DROP TABLE IF EXISTS `institution_subject_students`;
RENAME TABLE `z_3562_institution_subject_students` TO `institution_subject_students`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3562';
