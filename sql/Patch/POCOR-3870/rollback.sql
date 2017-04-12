-- `institution_subject_students`
DROP TABLE IF EXISTS `institution_subject_students`;

RENAME TABLE `z_3870_institution_subject_students_1` TO `institution_subject_students`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3870';
