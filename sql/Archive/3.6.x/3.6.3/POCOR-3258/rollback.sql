-- drop config_product_lists table
DROP TABLE `config_product_lists`;

-- config_items
DELETE FROM `config_items` WHERE `type` = 'Product Lists';

UPDATE `config_items`
INNER JOIN `z_3258_config_items` ON `z_3258_config_items`.`code` = `config_items`.`code`
SET `config_items`.`id` = `z_3258_config_items`.`id`;

DROP TABLE `z_3258_config_items`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3258';
