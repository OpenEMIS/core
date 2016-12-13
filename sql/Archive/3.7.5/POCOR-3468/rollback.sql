-- config_product_lists
ALTER TABLE `config_product_lists`
DROP COLUMN `file_content`,
DROP COLUMN `file_name`,
DROP COLUMN `deletable`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3468';
