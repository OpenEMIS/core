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
DELETE FROM `labels` WHERE `id` = '266f4853-80b3-11e6-a577-525400b263eb';
DELETE FROM `labels` WHERE `id` = '1d17a9f0-80b3-11e6-a577-525400b263eb';
DELETE FROM `labels` WHERE `id` = '0f930675-80b3-11e6-a577-525400b263eb';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2378';
