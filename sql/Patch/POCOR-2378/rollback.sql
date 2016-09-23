DROP TABLE `examinations`;

DROP TABLE `examination_items`;

DROP TABLE `examination_grading_types`;

DROP TABLE `examination_grading_options`;

DROP TABLE `examination_centres`;

DROP TABLE `examination_centre_subjects`;

DROP TABLE `examination_centre_special_needs`;

DROP TABLE `examination_centre_students`;

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 1045;
DELETE FROM `security_functions` WHERE `id` = 1046;
DELETE FROM `security_functions` WHERE `id` = 5044;
DELETE FROM `security_functions` WHERE `id` = 5045;
DELETE FROM `security_functions` WHERE `id` = 5046;
DELETE FROM `security_functions` WHERE `id` = 5047;
DELETE FROM `security_functions` WHERE `id` = 5048;

-- labels
DELETE FROM `labels` WHERE `module` = 'InstitutionExaminationStudents' AND `field` = 'examination_centre_id';
DELETE FROM `labels` WHERE `module` = 'InstitutionExaminationStudents' AND `field` = 'openemis_no';
DELETE FROM `labels` WHERE `module` = 'ExaminationCentreStudents' AND `field` = 'openemis_no';
DELETE FROM `labels` WHERE `module` = 'ExaminationCentreNotRegisteredStudents' AND `field` = 'openemis_no';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2378';
