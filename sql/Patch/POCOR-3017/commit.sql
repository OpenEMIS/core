-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3017', NOW());

-- infrastructure_levels
RENAME TABLE `infrastructure_levels` TO `z_3017_infrastructure_levels`;

DROP TABLE IF EXISTS `infrastructure_levels`;
CREATE TABLE IF NOT EXISTS `infrastructure_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text,
  `editable` int(11) NOT NULL DEFAULT '1',
  `parent_id` int(11) DEFAULT NULL,
  `lft` int(11) DEFAULT NULL,
  `rght` int(11) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `infrastructure_levels` (`id`, `code`, `name`, `description`, `editable`, `parent_id`, `lft`, `rght`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'LAND', 'Land', '', 0, NULL, 1, 8, NULL, NULL, 2, '0000-00-00 00:00:00'),
(2, 'BUILDING', 'Building', '', 0, 1, 2, 7, NULL, NULL, 2, '0000-00-00 00:00:00'),
(3, 'FLOOR', 'Floor', '', 0, 2, 3, 6, NULL, NULL, 2, '0000-00-00 00:00:00'),
(4, 'ROOM', 'Room', '', 0, 3, 4, 5, NULL, NULL, 2, '0000-00-00 00:00:00');

-- infrastructure_types
RENAME TABLE `infrastructure_types` TO `z_3017_infrastructure_types`;

DROP TABLE IF EXISTS `infrastructure_types`;
CREATE TABLE IF NOT EXISTS `infrastructure_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `infrastructure_level_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- room_types
DROP TABLE IF EXISTS `room_types`;
CREATE TABLE IF NOT EXISTS `room_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- institution_rooms
DROP TABLE IF EXISTS `institution_rooms`;
CREATE TABLE IF NOT EXISTS `institution_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  `start_date` date NOT NULL,
  `start_year` int(4) NOT NULL,
  `end_date` date DEFAULT NULL,
  `end_year` int(4) DEFAULT NULL,
  `institution_infrastructure_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `infrastructure_condition_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_infrastructure_id` (`institution_infrastructure_id`),
  KEY `institution_id` (`institution_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `room_type_id` (`room_type_id`),
  KEY `infrastructure_condition_id` (`infrastructure_condition_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- custom field
RENAME TABLE `infrastructure_custom_table_columns` TO `z_3017_infrastructure_custom_table_columns`;
RENAME TABLE `infrastructure_custom_table_rows` TO `z_3017_infrastructure_custom_table_rows`;
RENAME TABLE `infrastructure_custom_table_cells` TO `z_3017_infrastructure_custom_table_cells`;

-- room_custom_field_values
DROP TABLE IF EXISTS `room_custom_field_values`;
CREATE TABLE IF NOT EXISTS `room_custom_field_values` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `institution_room_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- custom_modules
CREATE TABLE `z_3017_custom_modules` LIKE  `custom_modules`;
INSERT INTO `z_3017_custom_modules` SELECT * FROM `custom_modules` WHERE 1;

ALTER TABLE `custom_modules` DROP `filter`;

UPDATE `custom_modules` SET `name` = 'Institution > Infrastructure', `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,DATE,TIME,FILE,COORDINATES' WHERE `custom_modules`.`code` = 'Infrastructure';
INSERT INTO `custom_modules` (`code`, `name`, `model`, `behavior`, `supported_field_types`, `visible`, `parent_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Room', 'Institution > Room', 'Institution.InstitutionRooms', NULL, 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,DATE,TIME,FILE,COORDINATES', 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00');
