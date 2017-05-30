ALTER TABLE `system_errors` DROP `code`;
ALTER TABLE `system_errors` DROP `request_method`;
ALTER TABLE `system_errors` DROP `server_info`;

DROP TABLE `config_product_lists`;

ALTER TABLE `z_3714_config_product_lists`
RENAME TO  `config_product_lists` ;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3714';
