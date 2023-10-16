-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3714', NOW());

ALTER TABLE `system_errors` ADD `code` INT(5) NOT NULL AFTER `id`;
ALTER TABLE `system_errors` ADD `request_method` VARCHAR(10) NOT NULL AFTER `error_message`;
ALTER TABLE `system_errors` ADD `server_info` TEXT NOT NULL AFTER `stack_trace`;

CREATE TABLE `z_3714_config_product_lists` LIKE `config_product_lists`;

INSERT INTO `z_3714_config_product_lists`
SELECT * FROM `config_product_lists`;

Update `config_product_lists`
SET `auto_login_url` = CONCAT(TRIM(TRAILING '/' FROM `auto_login_url`), '/'), `auto_logout_url` = CONCAT(TRIM(TRAILING '/' FROM `auto_logout_url`), '/')
WHERE `deletable` = 0;
