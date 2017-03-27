-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3846', NOW());

-- config_product_lists
CREATE TABLE `z_3846_config_product_lists` LIKE `config_product_lists`;

INSERT INTO `z_3846_config_product_lists`
SELECT * FROM `config_product_lists`;

ALTER TABLE `config_product_lists`
DROP COLUMN `deletable`;
