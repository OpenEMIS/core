-- config_product_lists
ALTER TABLE `config_product_lists`
ADD COLUMN `deletable` INT(1) NOT NULL DEFAULT 1 AFTER `url`;

UPDATE `config_product_lists`
INNER JOIN `z_3846_config_product_lists` ON `z_3846_config_product_lists`.`id` = `config_product_lists`.`id`
SET `config_product_lists`.`deletable` = `z_3846_config_product_lists`.`deletable`;

ALTER TABLE `config_product_lists`
CHANGE COLUMN `deletable` `deletable` INT(1) NOT NULL ;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3846';
