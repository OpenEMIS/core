DROP TABLE IF EXISTS `employment_types`;
DROP TABLE IF EXISTS `extracurricular_types`;
DROP TABLE IF EXISTS `identity_types`;
DROP TABLE IF EXISTS `languages`;
DROP TABLE IF EXISTS `license_types`;
DROP TABLE IF EXISTS `special_need_types`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-1798';
