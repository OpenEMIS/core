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
DELETE FROM `labels` WHERE `id` = 'bc9e63f6-8166-11e6-8b8d-525400b263eb';
DELETE FROM `labels` WHERE `id` = '8d828350-8171-11e6-9356-a090effc25c0';
DELETE FROM `labels` WHERE `id` = '954afaea-8171-11e6-9356-a090effc25c0';
DELETE FROM `labels` WHERE `id` = '9d78a938-8171-11e6-9356-a090effc25c0';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2378';
