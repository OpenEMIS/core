
-- config_items
DELETE FROM `config_items` WHERE `code` = 'external_data_source_type';

-- config_item_options
DELETE FROM `config_item_options` WHERE `option_type` = 'external_data_source_type';

-- external_data_source_attributes
DROP TABLE `external_data_source_attributes`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2827';
