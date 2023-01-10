-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3258', NOW());

-- create config_product_lists table
CREATE TABLE `config_product_lists` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(50) NOT NULL,
`url` text NULL,
`modified_user_id` int(11) NULL,
`modified` datetime NULL,
`created_user_id` int(11) NOT NULL,
`created` datetime NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- config_items
INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`)
VALUES ('1000', 'Product Lists', 'product_lists', 'Product Lists', 'Product Lists', '0', '0', '0', '1', '', '', '1', NOW());

CREATE TABLE `z_3258_config_items` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_3258_config_items`
SELECT `id`, `code`
FROM `config_items`
WHERE `code` = 'authentication_type';

UPDATE `config_items`
SET `id` = 1001
WHERE `code` = 'authentication_type';
