-- drop config_product_lists table
DROP TABLE `config_product_lists`;

-- config_items
DELETE FROM `config_items` WHERE `type` = 'Product Lists';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3258';
