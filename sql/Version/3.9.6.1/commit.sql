-- POCOR-3884
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3884', NOW());

-- config_items
INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`)
VALUES (1004, 'Webhooks', 'webhooks', 'Webhooks', 'Webhooks', 0, 0, 1, 1, '', '', 1, NOW());

-- webhooks
CREATE TABLE `webhooks` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  `status` INT(1) NOT NULL COMMENT '0 -> Inactive, 1 -> Active',
  `url` VARCHAR(200) NOT NULL,
  `method` VARCHAR(10) NOT NULL COMMENT 'POST -> HTTP Post Method, GET -> HTTP Get Method, PUT -> HTTP Put Method, DELETE -> HTTP Delete Method, PATCH -> HTTP Patch Method',
  `description` TEXT NULL,
  `modified_user_id` INT(11) NULL,
  `modified` DATETIME NULL,
  `created_user_id` INT(11) NOT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of webhooks';

-- webhook_events
CREATE TABLE `webhook_events` (
  `webhook_id` INT(11) NOT NULL,
  `event_key` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`webhook_id`, `event_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of webhook events';

-- config_product_lists
CREATE TABLE `z_3884_config_product_lists` LIKE `config_product_lists`;

INSERT INTO `z_3884_config_product_lists`
SELECT * FROM `config_product_lists`;

UPDATE `config_product_lists`
SET `config_product_lists`.`url` = `config_product_lists`.`auto_login_url`
WHERE `config_product_lists`.`auto_login_url` IS NOT NULL OR `config_product_lists`.`auto_login_url` <> '';

ALTER TABLE `config_product_lists`
DROP COLUMN `auto_logout_url`,
DROP COLUMN `auto_login_url`;

-- security_user_sessions
CREATE TABLE `security_user_sessions` (
  `id` VARCHAR(40) NOT NULL default '',
  `username` VARCHAR(50) NOT NULL default '',
  PRIMARY KEY (`id`, `username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of user sessions';


-- 3.9.6.1
UPDATE config_items SET value = '3.9.6.1' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
