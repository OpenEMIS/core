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

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `created_user_id`, `created`) VALUES
(uuid(), 'InstitutionQualityVisits', 'file_content', 'Institution > Visits', 'Attachment', NULL, NULL, 1, 1, NOW()),
(uuid(), 'InstitutionQualityVisits', 'quality_visit_type_id', 'Institution > Visits', 'Visit Type', NULL, NULL, 1, 1, NOW()),
(uuid(), 'InstitutionQualityVisits', 'institution_class_id', 'Institution > Visits', 'Subject', NULL, NULL, 1, 1, NOW());
