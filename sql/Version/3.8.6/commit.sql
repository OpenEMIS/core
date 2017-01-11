-- POCOR-3672
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3672', NOW());

-- security_user_logins
ALTER TABLE `security_user_logins`
ADD COLUMN `session_id` VARCHAR(45) NULL AFTER `login_date_time`,
ADD COLUMN `ip_address` VARCHAR(45) NULL AFTER `session_id`;

-- single_logout
CREATE TABLE `single_logout` (
  `username` VARCHAR(256) NOT NULL,
  `url` VARCHAR(256) NOT NULL,
  `session_id` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`username`, `url`, `session_id`),
  INDEX `username` (`username`),
  INDEX `url` (`url`),
  INDEX `session_id` (`session_id`));

-- config_product_list
CREATE TABLE `z_3672_config_product_lists` LIKE `config_product_lists`;

INSERT INTO `z_3672_config_product_lists`
SELECT * FROM `config_product_lists` WHERE `deletable` = 0;

ALTER TABLE `config_product_lists`
ADD COLUMN `auto_login_url` TEXT NULL AFTER `url`,
ADD COLUMN `auto_logout_url` TEXT NULL AFTER `auto_login_url`;

UPDATE `config_product_lists`
SET `url` = TRIM(TRAILING '/' FROM `url`),  `auto_login_url` = CONCAT(TRIM(TRAILING '/' FROM `url`), '/Login'), `auto_logout_url` = TRIM(TRAILING '/' FROM `url`)
WHERE `deletable` = 0;


-- POCOR-2828
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-2828', NOW());

UPDATE `security_functions` SET `_add`='StaffUser.add|getUniqueOpenemisId' WHERE `id`='1044';
UPDATE `security_functions` SET `_add`='Staff.add|getInstitutionPositions' WHERE `id`='1016';
UPDATE `security_functions` SET `_view`='Directories.index|Directories.view', `_edit`='Directories.edit|Directories.pull', `_add`='Directories.add', `_delete`='Directories.remove' WHERE `id`='7000';

INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`)
SELECT * FROM (SELECT '653f3552-d32e-11e6-9166-525400b263eb', 'OpenEMIS Identity', 'address_mapping' as field, 'address_mapping', 'address', NOW(), 1) as tmp
WHERE NOT EXISTS (
    SELECT `id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id` FROM `external_data_source_attributes` WHERE `external_data_source_type` = 'OpenEMIS Identity' AND `attribute_field` = 'address_mapping'
);

INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`)
SELECT * FROM (SELECT '858682fb-d583-11e6-87d6-525400b263eb', 'OpenEMIS Identity', 'postal_mapping' as field, 'postal_mapping', 'postal_code', NOW(), 1) as tmp
WHERE NOT EXISTS (
    SELECT `id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id` FROM `external_data_source_attributes` WHERE `external_data_source_type` = 'OpenEMIS Identity' AND `attribute_field` = 'postal_mapping'
);

INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`)
SELECT * FROM (SELECT '51528c40-d331-11e6-9166-525400b263eb', 'Custom', 'address_mapping' as field, 'address_mapping', '', NOW(), 1) as tmp
WHERE NOT EXISTS (
    SELECT `id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id` FROM `external_data_source_attributes` WHERE `external_data_source_type` = 'Custom' AND `attribute_field` = 'address_mapping'
);

INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`)
SELECT * FROM (SELECT '615a0770-d584-11e6-87d6-525400b263eb', 'Custom', 'postal_mapping' as field, 'postal_mapping', '', NOW(), 1) as tmp
WHERE NOT EXISTS (
    SELECT `id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id` FROM `external_data_source_attributes` WHERE `external_data_source_type` = 'Custom' AND `attribute_field` = 'postal_mapping'
);


-- 3.8.6
UPDATE config_items SET value = '3.8.6' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
