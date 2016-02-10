-- authentication_type_attributes
DROP TABLE `authentication_type_attributes`;

ALTER TABLE `z_2526_authentication_type_attributes` 
RENAME TO `authentication_type_attributes`;

-- config_item_options
DELETE FROM `config_item_options` WHERE `option_type` = 'authentication_type' AND `option` = 'Saml2';

UPDATE `config_items` INNER JOIN `z_2526_config_items` ON `z_2526_config_items`.`id` = `config_items`.`id`
SET `config_items`.`value` = `z_2526_config_items`.`value`;

DROP TABLE `z_2526_config_items`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2526';