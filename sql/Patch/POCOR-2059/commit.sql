-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-2059', NOW());

ALTER TABLE institution_subject_students DROP PRIMARY KEY, ADD PRIMARY KEY(student_id, academic_period_id, education_subject_id, education_grade_id);
