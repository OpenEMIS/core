INSERT INTO `db_patches` VALUES ('PHPOE-1825');

ALTER TABLE `student_guardians` CHANGE `id` `id` CHAR(36) NOT NULL;
ALTER TABLE `student_guardians` CHANGE `student_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_guardians` CHANGE `guardian_user_id` `guardian_id` INT(11) NOT NULL COMMENT 'links to security_users.id';

ALTER TABLE `security_users` ADD `is_student` INT(1) NOT NULL DEFAULT '0' AFTER `photo_content`;
ALTER TABLE `security_users` ADD INDEX(`is_student`);
ALTER TABLE `security_users` ADD `is_staff` INT(1) NOT NULL DEFAULT '0' AFTER `is_student`;
ALTER TABLE `security_users` ADD INDEX(`is_staff`);
ALTER TABLE `security_users` ADD `is_guardian` INT(1) NOT NULL DEFAULT '0' AFTER `is_staff`;
ALTER TABLE `security_users` ADD INDEX(`is_guardian`);

UPDATE `security_users`
JOIN `security_user_types` 
ON `security_user_types`.`security_user_id` = `security_users`.`id`
AND `security_user_types`.`user_type` = 1
SET `is_student` = 1;

UPDATE `security_users`
JOIN `security_user_types` 
ON `security_user_types`.`security_user_id` = `security_users`.`id`
AND `security_user_types`.`user_type` = 2
SET `is_staff` = 1;

UPDATE `security_users`
JOIN `security_user_types` 
ON `security_user_types`.`security_user_id` = `security_users`.`id`
AND `security_user_types`.`user_type` = 3
SET `is_guardian` = 1;

RENAME TABLE `security_user_types` TO `z_1825_security_user_types`;

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
