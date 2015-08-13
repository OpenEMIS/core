INSERT INTO `db_patches` VALUES ('PHPOE-1825');

ALTER TABLE `student_guardians` CHANGE `id` `id` CHAR(36) NOT NULL;
ALTER TABLE `student_guardians` CHANGE `student_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_guardians` CHANGE `guardian_user_id` `guardian_id` INT(11) NOT NULL COMMENT 'links to security_users.id';

DROP TABLE IF EXISTS `census_staff`;
DROP TABLE IF EXISTS `census_attendances`;
DROP TABLE IF EXISTS `census_assessments`;
DROP TABLE IF EXISTS `datawarehouse_dimensions`;
DROP TABLE IF EXISTS `datawarehouse_fields`;
DROP TABLE IF EXISTS `datawarehouse_indicators`;
DROP TABLE IF EXISTS `datawarehouse_indicator_dimensions`;
DROP TABLE IF EXISTS `datawarehouse_indicator_subgroups`;
DROP TABLE IF EXISTS `datawarehouse_modules`;
DROP TABLE IF EXISTS `datawarehouse_units`;
DROP TABLE IF EXISTS `navigations`;
DROP TABLE IF EXISTS `batch_indicators`;
DROP TABLE IF EXISTS `batch_indicator_subgroups`;
DROP TABLE IF EXISTS `batch_indicator_results`;
DROP TABLE IF EXISTS `olap_cubes`;
DROP TABLE IF EXISTS `olap_cube_dimensions`;
DROP TABLE IF EXISTS `population`;
DROP TABLE IF EXISTS `public_expenditure`;
DROP TABLE IF EXISTS `public_expenditure_education_level`;
