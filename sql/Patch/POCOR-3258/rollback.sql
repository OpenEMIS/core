-- drop config_product_lists table
DROP TABLE `config_product_lists`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3258';
