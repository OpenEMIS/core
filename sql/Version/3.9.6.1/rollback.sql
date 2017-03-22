-- POCOR-3884
-- config_items
DELETE FROM `config_items` WHERE `id` = 1004;

-- webhooks
DROP TABLE `webhooks`;

-- webhook_events
DROP TABLE `webhook_events`;

-- config_product_lists
ALTER TABLE `config_product_lists`
ADD COLUMN `auto_login_url` TEXT NULL AFTER `url`,
ADD COLUMN `auto_logout_url` TEXT NULL AFTER `auto_login_url`;

UPDATE `config_product_lists`
INNER JOIN `z_3884_config_product_lists` on `z_3884_config_product_lists`.`id` = `config_product_lists`.`id`
SET `config_product_lists`.`auto_login_url` = `z_3884_config_product_lists`.`auto_login_url`,
    `config_product_lists`.`auto_logout_url` = `z_3884_config_product_lists`.`auto_logout_url`,
    `config_product_lists`.`url` = `z_3884_config_product_lists`.`url`;

DROP TABLE `z_3884_config_product_lists`;

-- security_user_sessions
DROP TABLE `security_user_sessions`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3884';


-- 3.9.6
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.6' WHERE code = 'db_version';
