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

DROP TABLE IF EXISTS `room_statuses`;
CREATE TABLE IF NOT EXISTS `room_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `room_statuses` (`id`, `code`, `name`) VALUES
(1, 'IN_USE', 'In Use'),
(2, 'END_OF_USAGE', 'End of Usage'),
(3, 'CHANGE_IN_ROOM_TYPE', 'Change in Room Type');

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
  `end_date` date NOT NULL,
  `end_year` int(4) NOT NULL,
  `room_status_id` int(11) NOT NULL,
  `institution_infrastructure_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `infrastructure_condition_id` int(11) NOT NULL,
  `previous_room_id` int(11) NOT NULL COMMENT 'links to institution_rooms.id',
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
CREATE TABLE `z_3017_infrastructure_custom_forms` LIKE  `infrastructure_custom_forms`;
INSERT INTO `z_3017_infrastructure_custom_forms` SELECT * FROM `infrastructure_custom_forms` WHERE 1;

CREATE TABLE `z_3017_infrastructure_custom_forms_filters` LIKE  `infrastructure_custom_forms_filters`;
INSERT INTO `z_3017_infrastructure_custom_forms_filters` SELECT * FROM `infrastructure_custom_forms_filters` WHERE 1;

RENAME TABLE `institution_infrastructures` TO `z_3017_institution_infrastructures`;
CREATE TABLE `institution_infrastructures` LIKE  `z_3017_institution_infrastructures`;

RENAME TABLE `infrastructure_custom_field_values` TO `z_3017_infrastructure_custom_field_values`;
CREATE TABLE `infrastructure_custom_field_values` LIKE  `z_3017_infrastructure_custom_field_values`;

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
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `infrastructure_custom_field_id` (`infrastructure_custom_field_id`),
  KEY `institution_room_id` (`institution_room_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- custom_modules
CREATE TABLE `z_3017_custom_modules` LIKE  `custom_modules`;
INSERT INTO `z_3017_custom_modules` SELECT * FROM `custom_modules` WHERE 1;

ALTER TABLE `custom_modules` DROP `filter`;
ALTER TABLE `custom_modules` DROP `behavior`;
ALTER TABLE `custom_modules` DROP `supported_field_types`;

INSERT INTO `custom_modules` (`code`, `name`, `model`, `visible`, `parent_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Room', 'Institution > Room', 'Institution.InstitutionRooms', 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- security_functions
UPDATE `security_functions`
SET `_view` = 'Fields.index|Fields.view|Pages.index|Pages.view|Types.index|Types.view|RoomPages.index|RoomPages.view|RoomTypes.index|RoomTypes.view', `_edit` = 'Fields.edit|Pages.edit|Types.edit|RoomPages.edit|RoomTypes.edit', `_add` = 'Fields.add|Pages.add|Types.add|RoomPages.add|RoomTypes.add', `_delete` = 'Fields.remove|Pages.remove|Types.remove|RoomPages.remove|RoomTypes.remove'
WHERE id = 5018;

UPDATE `security_functions`
SET `_view` = 'Infrastructures.index|Infrastructures.view|Rooms.index|Rooms.view', `_edit` = 'Infrastructures.edit|Rooms.edit', `_add` = 'Infrastructures.add|Rooms.add', `_delete` = 'Infrastructures.remove|Rooms.remove'
WHERE id = 1011;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)  VALUES (uuid(), 'InstitutionRooms', 'institution_infrastructure_id', 'Institutions -> Rooms', 'Parent', 1, 1, NOW());
