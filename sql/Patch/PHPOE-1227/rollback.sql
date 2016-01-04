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

-- field_options
DELETE FROM `field_options` WHERE `parent` = 'Health';

-- security_functions
DELETE FROM `security_functions` WHERE `id` IN (7037, 7038, 7039, 7040, 7041, 7042, 7043, 7044);
DELETE FROM `security_functions` WHERE `id` IN (2021, 2022, 2023, 2024, 2025, 2026, 2027, 2028);
DELETE FROM `security_functions` WHERE `id` IN (3028, 3029, 3030, 3031, 3032, 3033, 3034, 3035);

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1227';
