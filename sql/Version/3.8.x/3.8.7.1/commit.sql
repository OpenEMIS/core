-- POCOR-3720
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3720', NOW());

-- assessment_items
RENAME TABLE `assessment_items` TO `z_3720_assessment_items`;

DROP TABLE IF EXISTS `assessment_items`;
CREATE TABLE IF NOT EXISTS `assessment_items` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` decimal(6,2) DEFAULT '0.00',
  `classification` varchar(250) DEFAULT NULL,
  `assessment_id` int(11) NOT NULL COMMENT 'links to assessments.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `assessment_id` (`assessment_id`),
  INDEX `education_subject_id` (`education_subject_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of assessment items for a specific assessment';

INSERT INTO `assessment_items` (`id`, `weight`, `classification`, `assessment_id`, `education_subject_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `weight`, NULL, `assessment_id`, `education_subject_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_3720_assessment_items`;


-- POCOR-3752
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3752', NOW());

-- code here
-- change the decimal to 3 digits
ALTER TABLE `competencies` CHANGE `max` `max` DECIMAL(5,2) NOT NULL;
ALTER TABLE `competencies` CHANGE `min` `min` DECIMAL(5,2) NOT NULL;
ALTER TABLE `staff_appraisals_competencies` CHANGE `rating` `rating` DECIMAL(5,2) NULL DEFAULT NULL;
ALTER TABLE `staff_appraisals` CHANGE `final_rating` `final_rating` DECIMAL(7,2) NOT NULL;

-- staff_appraisals adding file upload
ALTER TABLE `staff_appraisals` ADD `file_name` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `comment`;
ALTER TABLE `staff_appraisals` ADD `file_content` LONGBLOB NULL DEFAULT NULL AFTER `file_name`;

-- permission for download appraisal attachment
UPDATE `security_functions` SET `_execute` = 'StaffAppraisals.download' WHERE `id` = 3037;


-- 3.8.7.1
UPDATE config_items SET value = '3.8.7.1' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
