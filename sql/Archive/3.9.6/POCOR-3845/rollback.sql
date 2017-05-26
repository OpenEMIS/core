-- institution_student_absences
DROP TABLE IF EXISTS `institution_student_absences`;
RENAME TABLE `z_3845_institution_student_absences` TO `institution_student_absences`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3845';
