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
