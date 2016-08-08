INSERT INTO `db_patches` VALUES ('PHPOE-1787', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES 
(uuid(), 'BankAccounts', 'remarks', 'Students -> Bank Accounts | Staff -> Bank Accounts', 'Comments', 1, NOW()),
(uuid(), 'InstitutionBankAccounts', 'remarks', 'Institutions -> Bank Accounts', 'Comments', 1, NOW());

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2023', NOW());

-- Backup tables
CREATE TABLE `z_2023_institution_quality_visits` LIKE `institution_quality_visits`;
INSERT INTO `z_2023_institution_quality_visits` SELECT * FROM `institution_quality_visits` WHERE 1;

-- Alter table
ALTER TABLE `institution_quality_visits` DROP `education_grade_id`;
ALTER TABLE `institution_quality_visits` DROP `institution_section_id`;
ALTER TABLE `institution_quality_visits` ADD `file_name` VARCHAR(250) NULL DEFAULT NULL AFTER `comment`;
ALTER TABLE `institution_quality_visits` ADD `file_content` LONGBLOB NULL DEFAULT NULL AFTER `file_name`;

-- quality_visit_types
DROP TABLE IF EXISTS `quality_visit_types`;
CREATE TABLE IF NOT EXISTS `quality_visit_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- field_options
UPDATE `field_options` SET `params` = '{"model":"FieldOption.QualityVisitTypes"}' WHERE `code` = 'QualityVisitTypes';
ALTER TABLE `field_option_values` ADD `id_new` int(11) DEFAULT NULL AFTER `id`;

SET @parentId := 0;
SELECT `id` INTO @parentId FROM `field_options` WHERE `code` = 'QualityVisitTypes';

INSERT INTO `quality_visit_types` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`,`created`)
SELECT `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `id`, `created` FROM `field_option_values` WHERE `field_option_id` = @parentId;

UPDATE `field_option_values` SET `visible` = 0 WHERE `field_option_id` = @parentId;

UPDATE `field_option_values` AS `FieldOptionValues`
INNER JOIN `quality_visit_types` AS `QualityVisitTypes` ON `QualityVisitTypes`.`created_user_id` = `FieldOptionValues`.`id`
SET `FieldOptionValues`.`id_new` = `QualityVisitTypes`.`id`;

UPDATE `institution_quality_visits` AS `InstitutionQualityVisits`
INNER JOIN `field_option_values` AS `FieldOptionValues` ON `FieldOptionValues`.`id` = `InstitutionQualityVisits`.`quality_visit_type_id`
SET `InstitutionQualityVisits`.`quality_visit_type_id` = `FieldOptionValues`.`id_new`;

UPDATE `quality_visit_types` AS `QualityVisitTypes`
INNER JOIN `field_option_values` AS `FieldOptionValues` ON `FieldOptionValues`.`id_new` = `QualityVisitTypes`.`id`
SET `QualityVisitTypes`.`created_user_id` = `FieldOptionValues`.`created_user_id`;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `created_user_id`, `created`) VALUES
(uuid(), 'InstitutionQualityVisits', 'file_content', 'Institution > Visits', 'Attachment', NULL, NULL, 1, 1, NOW()),
(uuid(), 'InstitutionQualityVisits', 'quality_visit_type_id', 'Institution > Visits', 'Visit Type', NULL, NULL, 1, 1, NOW()),
(uuid(), 'InstitutionQualityVisits', 'institution_class_id', 'Institution > Visits', 'Subject', NULL, NULL, 1, 1, NOW());

--
-- PHPOE-2463
--

INSERT INTO `db_patches` VALUES ('PHPOE-2463', NOW());

CREATE TABLE `z_2463_institution_section_students` LIKE `institution_section_students`;
INSERT INTO `z_2463_institution_section_students` SELECT * FROM `institution_section_students`;

ALTER TABLE `institution_section_students` CHANGE `id` `id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- 
-- PHPOE-2436
--

INSERT INTO `db_patches` VALUES ('PHPOE-2436', NOW());

CREATE TABLE `z_2436_import_mapping` LIKE `import_mapping`;
INSERT INTO `z_2436_import_mapping` SELECT * FROM `import_mapping`;

DELETE FROM `import_mapping` WHERE `id`=11 or `id`=13;
UPDATE `import_mapping` SET `order` = `order`+1000 WHERE `order` > 14 and `model`='Institution.Institutions';
UPDATE `import_mapping` SET `order` = `order`-1001 WHERE `order` > 1000 and `model`='Institution.Institutions';
UPDATE `import_mapping` SET `order` = `order`+1000 WHERE `order` > 12 and `model`='Institution.Institutions';
UPDATE `import_mapping` SET `order` = `order`-1001 WHERE `order` > 1000 and `model`='Institution.Institutions';

UPDATE `import_mapping` SET `id` = `id`+1000 WHERE `id` > 12;
UPDATE `import_mapping` SET `id` = `id`-1001 WHERE `id` > 1000;

UPDATE `import_mapping` SET `id` = `id`+1000 WHERE `id` > 10;
UPDATE `import_mapping` SET `id` = `id`-1001 WHERE `id` > 1000;

UPDATE `import_mapping` SET `description` = '( DD/MM/YYYY )' WHERE `column_name` LIKE '%date%';

DROP TABLE IF EXISTS `z_2403_import_mapping`;
DROP TABLE IF EXISTS `z_2421_import_mapping`;


UPDATE config_items SET value = '3.4.9' WHERE code = 'db_version';
