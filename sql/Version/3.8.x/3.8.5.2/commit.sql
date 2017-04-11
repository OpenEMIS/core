-- POCOR-3672
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3672', NOW());

-- security_user_logins
ALTER TABLE `security_user_logins`
ADD COLUMN `session_id` VARCHAR(45) NULL AFTER `login_date_time`,
ADD COLUMN `ip_address` VARCHAR(45) NULL AFTER `session_id`;

-- single_logout
CREATE TABLE `single_logout` (
  `id` CHAR(36) NOT NULL,
  `username` VARCHAR(100) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `session_id` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `username` (`username`),
  INDEX `session_id` (`session_id`)
);

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


-- 3.8.5.2
UPDATE config_items SET value = '3.8.5.2' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
