-- config_item_options
DELETE FROM `config_item_options` WHERE `option_type` = 'authentication_type' AND `option` = 'Google';

-- config_items
UPDATE `config_items` SET `value` = 'Local' WHERE `type` = 'Authentication' AND `code` = 'authentication_type';

-- authentication_type_attributes
DROP TABLE `authentication_type_attributes`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2505';