DELETE FROM labels WHERE module = 'StudentUser' and field = 'openemis_no';
DELETE FROM labels WHERE module = 'StaffUser' and field = 'openemis_no';
DELETE FROM labels WHERE module = 'StudentAttendances' and field = 'openemis_no';
DELETE FROM labels WHERE module = 'StaffAttendances' and field = 'openemis_no';
DELETE FROM labels WHERE module = 'Directories' and field = 'openemis_no';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1808';

-- Drop new tables
DROP TABLE IF EXISTS `user_healths`;
DROP TABLE IF EXISTS `user_health_allergies`;
DROP TABLE IF EXISTS `user_health_consultations`;
DROP TABLE IF EXISTS `user_health_families`;
DROP TABLE IF EXISTS `user_health_histories`;
DROP TABLE IF EXISTS `user_health_immunizations`;
DROP TABLE IF EXISTS `user_health_medications`;
DROP TABLE IF EXISTS `user_health_tests`;

DROP TABLE IF EXISTS `health_allergy_types`;
DROP TABLE IF EXISTS `health_consultation_types`;
DROP TABLE IF EXISTS `health_relationships`;
DROP TABLE IF EXISTS `health_conditions`;
DROP TABLE IF EXISTS `health_immunization_types`;
DROP TABLE IF EXISTS `health_test_types`;

-- restore tables
RENAME TABLE `z_1227_staff_healths` TO `staff_healths`;
RENAME TABLE `z_1227_staff_health_allergies` TO `staff_health_allergies`;
RENAME TABLE `z_1227_staff_health_consultations` TO `staff_health_consultations`;
RENAME TABLE `z_1227_staff_health_families` TO `staff_health_families`;
RENAME TABLE `z_1227_staff_health_histories` TO `staff_health_histories`;
RENAME TABLE `z_1227_staff_health_immunizations` TO `staff_health_immunizations`;
RENAME TABLE `z_1227_staff_health_medications` TO `staff_health_medications`;
RENAME TABLE `z_1227_staff_health_tests` TO `staff_health_tests`;

RENAME TABLE `z_1227_student_healths` TO `student_healths`;
RENAME TABLE `z_1227_student_health_allergies` TO `student_health_allergies`;
RENAME TABLE `z_1227_student_health_consultations` TO `student_health_consultations`;
RENAME TABLE `z_1227_student_health_families` TO `student_health_families`;
RENAME TABLE `z_1227_student_health_histories` TO `student_health_histories`;
RENAME TABLE `z_1227_student_health_immunizations` TO `student_health_immunizations`;
RENAME TABLE `z_1227_student_health_medications` TO `student_health_medications`;
RENAME TABLE `z_1227_student_health_tests` TO `student_health_tests`;

-- field_options
DELETE FROM `field_options` WHERE `parent` = 'Health';

-- security_functions
DELETE FROM `security_functions` WHERE `id` IN (7037, 7038, 7039, 7040, 7041, 7042, 7043, 7044);
DELETE FROM `security_functions` WHERE `id` IN (2021, 2022, 2023, 2024, 2025, 2026, 2027, 2028);
DELETE FROM `security_functions` WHERE `id` IN (3028, 3029, 3030, 3031, 3032, 3033, 3034, 3035);

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1227';

DELETE FROM labels WHERE module = 'StudentPromotion' and field = 'fromAcademicPeriod';
DELETE FROM labels WHERE module = 'StudentPromotion' and field = 'toAcademicPeriod';
DELETE FROM labels WHERE module = 'StudentPromotion' and field = 'fromGrade';
DELETE FROM labels WHERE module = 'StudentPromotion' and field = 'toGrade';
DELETE FROM labels WHERE module = 'StudentPromotion' and field = 'status';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2291';

-- 
-- PHPOE-832
--

DROP TABLE `config_items`;
ALTER TABLE `z_832_config_items` RENAME `config_items`;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-832';

UPDATE config_items SET value = '3.4.6' WHERE code = 'db_version';
