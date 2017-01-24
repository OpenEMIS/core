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
