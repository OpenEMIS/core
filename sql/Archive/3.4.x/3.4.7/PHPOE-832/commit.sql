-- 
-- PHPOE-832
--

INSERT INTO `db_patches` VALUES ('PHPOE-832', NOW());

CREATE TABLE `z_832_config_items` LIKE `config_items`;
INSERT INTO `z_832_config_items` SELECT * FROM `config_items`;

INSERT INTO `config_items`
(`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
(NULL, 'Google Map Center Longitude', 'google_map_center_longitude', 'Map', 'Google Map Center Longitude', '0', '0', '1', '1', '', '', 1, NOW()),
(NULL, 'Google Map Center Latitude', 'google_map_center_latitude', 'Map', 'Google Map Center Latitude', '0', '0', '1', '1', '', '', 1, NOW()),
(NULL, 'Google Map Zoom', 'google_map_zoom', 'Map', 'Google Map Zoom', '10', '10', '1', '1', '', '', 1, NOW());
