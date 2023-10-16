-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3845', NOW());

-- institution_student_absences
CREATE TABLE `z_3845_institution_student_absences` LIKE `institution_student_absences`;
INSERT INTO `z_3845_institution_student_absences` SELECT * FROM `institution_student_absences`;

UPDATE `institution_student_absences` SET `start_time` = NULL, `end_time` = NULL WHERE `full_day` = 1;
