-- config_items
CREATE TABLE IF NOT EXISTS `config_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `value` varchar(500) NOT NULL,
  `default_value` varchar(500) DEFAULT NULL,
  `editable` int(1) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `field_type` varchar(50) NOT NULL,
  `option_type` varchar(50) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fixing the ID of authentication type to 1001
INSERT IGNORE INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES (1001, 'Type', 'authentication_type', 'Authentication', 'Type', 'Local', 'Local', '1', '1', 'Dropdown', 'authentication_type', NULL, NULL, '1', NOW());

-- config_item_options
CREATE TABLE IF NOT EXISTS `config_item_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `option_type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `option` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(3) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `config_item_options` (`option_type`,`option`,`value`,`order`,`visible`) VALUES ('authentication_type','Local','Local',1,1);
INSERT INTO `config_item_options` (`option_type`,`option`,`value`,`order`,`visible`) VALUES ('authentication_type','LDAP','LDAP',2,0);
INSERT INTO `config_item_options` (`option_type`,`option`,`value`,`order`,`visible`) VALUES ('authentication_type','Google','Google',3,1);
INSERT INTO `config_item_options` (`option_type`,`option`,`value`,`order`,`visible`) VALUES ('authentication_type','Saml2','Saml2',4,1);

-- authentication_type_attributes
CREATE TABLE IF NOT EXISTS `authentication_type_attributes` (
  `id` char(36) NOT NULL,
  `authentication_type` varchar(50) NOT NULL,
  `attribute_field` varchar(50) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `value` text,
  `modified` datetime DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `created_user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
