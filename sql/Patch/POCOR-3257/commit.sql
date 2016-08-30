-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3257', NOW());


-- config_items
INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('Area (Education)', 'AreaEducation', 'Administrative Boundaries', 'Area (Education)', '0', '0', '0', '1', '', '', NULL, NULL, '2', NOW());


-- create config_product_lists table
CREATE TABLE `config_administrative_boundaries` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(50) NOT NULL,
`url` text NULL,
`modified_user_id` int(11) NULL,
`modified` datetime NULL,
`created_user_id` int(11) NOT NULL,
`created` datetime NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- config_administrative_boundaries
INSERT INTO `config_administrative_boundaries` (`name`, `url`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('API', '', NULL, NULL, '2', NOW());
