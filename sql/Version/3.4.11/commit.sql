INSERT INTO `db_patches` VALUES ('PHPOE-1508', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) 
VALUES (uuid(), 'Institutions', 'area_administrative_id', 'Institutions', 'Area (Administrative)', '0', NOW());

ALTER TABLE `institutions` 
CHANGE COLUMN `postal_code` `postal_code` VARCHAR(20) NULL;

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2484', NOW());

-- security_functions
UPDATE `security_functions` SET `_execute`='Promotion.index|Promotion.add|Promotion.reconfirm' WHERE `id`=1005;


-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2505', NOW());

-- config_item_options
INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) VALUES ('authentication_type', 'Google', 'Google', 3, 1);

UPDATE `config_items` SET `value` = 'Local', `default_value` = 'Local' WHERE `code` = 'authentication_type';

-- authentication_type_attributes
CREATE TABLE `authentication_type_attributes` (
  `id` CHAR(36) NOT NULL COMMENT '',
  `authentication_type` VARCHAR(50) NOT NULL COMMENT '',
  `attribute_field` VARCHAR(50) NOT NULL COMMENT '',
  `attribute_name` VARCHAR(50) NOT NULL COMMENT '',
  `value` VARCHAR(100) NULL COMMENT '',
  PRIMARY KEY (`id`));

-- db_patches
INSERT INTO db_patches VALUES ('PHPOE-2500', NOW());

-- institution_section_students
CREATE TABLE z_2500_institution_section_students LIKE institution_section_students;

INSERT INTO z_2500_institution_section_students
SELECT * FROM institution_section_students WHERE id = '';

UPDATE institution_section_students SET id = uuid() WHERE id = '';

-- For patching institution system groups
CREATE TABLE z_2500_security_groups LIKE security_groups;

INSERT INTO `z_2500_security_groups`
SELECT * FROM `security_groups` 
WHERE `security_groups`.`id` IN (
	SELECT `security_group_id` FROM `institutions`
);

UPDATE `security_groups` 
INNER JOIN `institutions` 
ON `institutions`.`security_group_id` = `security_groups`.`id`
SET `security_groups`.`name` = CONCAT(`institutions`.`code`, ' - ', `institutions`.`name`);

-- DROP previous versions backup table
DROP TABLE IF EXISTS `z2338_institution_section_students`;
DROP TABLE IF EXISTS `z_832_config_items`;
DROP TABLE IF EXISTS `z_1227_staff_healths`;
DROP TABLE IF EXISTS `z_1227_staff_health_allergies`;
DROP TABLE IF EXISTS `z_1227_staff_health_consultations`;
DROP TABLE IF EXISTS `z_1227_staff_health_families`;
DROP TABLE IF EXISTS `z_1227_staff_health_histories`;
DROP TABLE IF EXISTS `z_1227_staff_health_immunizations`;
DROP TABLE IF EXISTS `z_1227_staff_health_medications`;
DROP TABLE IF EXISTS `z_1227_staff_health_tests`;
DROP TABLE IF EXISTS `z_1227_student_healths`;
DROP TABLE IF EXISTS `z_1227_student_health_allergies`;
DROP TABLE IF EXISTS `z_1227_student_health_consultations`;
DROP TABLE IF EXISTS `z_1227_student_health_families`;
DROP TABLE IF EXISTS `z_1227_student_health_histories`;
DROP TABLE IF EXISTS `z_1227_student_health_immunizations`;
DROP TABLE IF EXISTS `z_1227_student_health_medications`;
DROP TABLE IF EXISTS `z_1227_student_health_tests`;
DROP TABLE IF EXISTS `z_1463_labels`;
DROP TABLE IF EXISTS `z_2023_institution_quality_visits`;
DROP TABLE IF EXISTS `z_2193_security_function`;
DROP TABLE IF EXISTS `z_2193_security_role_functions`;
DROP TABLE IF EXISTS `z_2403_labels`;
DROP TABLE IF EXISTS `z_2436_import_mapping`;

UPDATE config_items SET value = '3.4.11' WHERE code = 'db_version';
