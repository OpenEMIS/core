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

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1227';
