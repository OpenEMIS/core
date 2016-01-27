INSERT INTO `db_patches` VALUES ('PHPOE-1508', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) 
VALUES (uuid(), 'Institutions', 'area_administrative_id', 'Institutions', 'Area (Administrative)', '0', NOW());

ALTER TABLE `institutions` 
CHANGE COLUMN `postal_code` `postal_code` VARCHAR(20) NULL;

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2484', NOW());

-- security_functions
UPDATE `security_functions` SET `_execute`='Promotion.index|Promotion.add|Promotion.reconfirm' WHERE `id`=1005;


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

UPDATE config_items SET value = '3.4.11' WHERE code = 'db_version';
