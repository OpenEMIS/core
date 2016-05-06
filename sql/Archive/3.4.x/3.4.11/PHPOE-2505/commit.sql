-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2505', NOW());

-- config_item_options
INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) VALUES ('authentication_type', 'Google', 'Google', 3, 1);

UPDATE `config_items` SET `value` = 'Local', `default_value` = 'Local' WHERE `code` = 'authentication_type';

-- authentication_type_attributes
CREATE TABLE `authentication_type_attributes` (
  `id` CHAR(36) NOT NULL COMMENT '',
  `authentication_type` VARCHAR(50) NOT NULL COMMENT '',
  `attribute_field` VARCHAR(50) NOT NULL COMMENT '',
  `attribute_name` VARCHAR(50) NOT NULL COMMENT '',
  `value` VARCHAR(100) NULL COMMENT '',
  PRIMARY KEY (`id`));
