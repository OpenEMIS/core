-- config_items
UPDATE `config_items`
SET `value`='Local'
WHERE `id`='1001' AND `value`='OAuth2OpenIDConnect';

-- config_item_options
DELETE FROM `config_item_options` WHERE `id` = 37;

ALTER TABLE `config_item_options`
CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '' ;

ALTER TABLE `config_item_options`
CHANGE COLUMN `id` `id` INT(11) NOT NULL COMMENT '' ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3409';
