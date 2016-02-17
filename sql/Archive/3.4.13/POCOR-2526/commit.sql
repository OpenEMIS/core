-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2526', NOW());

-- authentication_type_attributes
ALTER TABLE `authentication_type_attributes` 
RENAME TO `z_2526_authentication_type_attributes`;

CREATE TABLE `authentication_type_attributes` (
  `id` char(36) NOT NULL,
  `authentication_type` varchar(50) NOT NULL,
  `attribute_field` varchar(50) NOT NULL,
  `attribute_name` varchar(50) NOT NULL,
  `value` text,
  `modified` datetime DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `created_user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- config_item_options
INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) VALUES ('authentication_type', 'Saml2', 'Saml2', 4, 1);

-- config_items
CREATE TABLE `z_2526_config_items` LIKE `config_items`;

INSERT INTO `z_2526_config_items` SELECT * FROM `config_items` WHERE `code` = 'authentication_type' AND `type` = 'Authentication';

UPDATE `config_items` SET `value` = 'Local' WHERE `code` = 'authentication_type' AND `type` = 'Authentication';