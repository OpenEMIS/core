
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2827', NOW());

-- config_items
INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`)
VALUES (1002, 'Type', 'external_data_source_type', 'External Data Source', 'Type', 'None', 'None', 1, 1, 'Dropdown', 'external_data_source_type', 1, NOW());

-- config_item_options
INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`) VALUES (100, 'external_data_source_type', 'None', 'None', 1, 1);
INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`) VALUES (101, 'external_data_source_type', 'OpenEMIS Identity', 'OpenEMIS Identity', 2, 1);

-- external_data_source_attributes
CREATE TABLE `external_data_source_attributes` (
  `id` char(36) NOT NULL,
  `external_data_source_type` varchar(50) NOT NULL,
  `attribute_field` varchar(50) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `value` text,
  `modified` datetime DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `created_user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
