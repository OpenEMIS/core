-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3258', NOW());

-- create config_product_lists table
CREATE TABLE `config_product_lists` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(50) NOT NULL,
`url` text NOT NULL,
`modified_user_id` int(11) NULL,
`modified` datetime NULL,
`created_user_id` int(11) NOT NULL,
`created` datetime NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- config_items
INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`)
VALUES ('Product Lists', 'product_lists', 'Product Lists', 'Product Lists', '0', '0', '0', '1', '', '', '1', NOW());
