INSERT INTO `config_items` (`id`, `name`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES 
('108', 'institution_site_area_level_id', 'Institution Site', 'Display Area Level', '3', '3', '1', '1', 'Dropdown', 'database:AreaLevel', '1', '2014-07-01');

INSERT INTO `config_item_options` (`id`,`option_type`, `option`, `value`, `order`, `visible`) VALUES 
('27','database:AreaLevel', 'AreaLevel.name', 'AreaLevel.id', '1', '1');

