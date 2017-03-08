-- POCOR-3672
-- security_user_logins
ALTER TABLE `security_user_logins`
DROP COLUMN `ip_address`,
DROP COLUMN `session_id`;

-- single_logout
DROP TABLE `single_logout`;

-- config_product_list
UPDATE `config_product_lists`
INNER JOIN `z_3672_config_product_lists` ON `config_product_lists`.`id` = `z_3672_config_product_lists`.`id`
SET `config_product_lists`.`url` = `z_3672_config_product_lists`.`url`;

DROP TABLE `z_3672_config_product_lists`;

-- system_patches
DELETE FROM `system_patches` WHERE 'issue' = 'POCOR-3672';


-- 3.8.5.1
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.8.5.1' WHERE code = 'db_version';
