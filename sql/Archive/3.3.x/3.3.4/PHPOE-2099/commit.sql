-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2099', NOW());

-- config_items
INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
('Url', 'support_url', 'Help', 'Url', 'https://support.openemis.org/core/', 'https://support.openemis.org/core/', 0, 1, '', '', 1, NOW());
