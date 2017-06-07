-- POCOR-3937
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3937', NOW());

-- import_mapping
INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
(112, 'Staff.Salaries', 'salary_date', '( DD/MM/YYYY )', 1, 0, NULL, NULL, NULL),
(113, 'Staff.Salaries', 'comment', '(Optional)', 2, 0, NULL, NULL, NULL),
(114, 'Staff.Salaries', 'gross_salary', NULL, 3, 0, NULL, NULL, NULL);

-- security_functions
UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `order` >= 3022
AND `order` < 4000;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('3039', 'Import Staff Salaries', 'Staff', 'Institutions', 'Staff - Finance', '3000', NULL, NULL, NULL, NULL, 'ImportSalaries.add|ImportSalaries.template|ImportSalaries.results|ImportSalaries.downloadFailed|ImportSalaries.downloadPassed', '3022', '1', NULL, NULL, NULL, '1', '2017-05-11');

UPDATE `security_functions` SET `_execute` = 'Salaries.excel' WHERE `security_functions`.`id` = 3020;

UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `order` >= 7038
AND `order` < 8000;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('7052', 'Import Staff Salaries', 'Directories', 'Directory', 'Staff - Finance', '7000', NULL, NULL, NULL, NULL, 'ImportSalaries.add|ImportSalaries.template|ImportSalaries.results|ImportSalaries.downloadFailed|ImportSalaries.downloadPassed', '7038', '1', NULL, NULL, NULL, '1', '2017-05-11');

UPDATE `security_functions` SET `_execute` = 'StaffSalaries.excel' WHERE `security_functions`.`id` = 7034;


-- POCOR-3853
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3853', NOW());

-- infrastructure_statuses
CREATE TABLE `infrastructure_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT INTO `infrastructure_statuses`
SELECT * FROM `room_statuses`;

UPDATE `infrastructure_statuses`
SET `code` = 'CHANGE_IN_TYPE', `name` = 'Change in Type'
WHERE `code` = 'CHANGE_IN_ROOM_TYPE';

-- institution_lands
CREATE TABLE IF NOT EXISTS `institution_lands` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(100) NOT NULL,
  `name` VARCHAR(250) NOT NULL,
  `start_date` DATE NULL,
  `start_year` INT(4) NULL,
  `end_date` DATE NULL,
  `end_year` INT(4) NULL,
  `year_acquired` INT(4) NULL,
  `year_disposed` INT(4) NULL,
  `area` FLOAT NULL,
  `comment` TEXT NULL,
  `institution_id` INT(11) NOT NULL COMMENT 'links to institutions.id',
  `academic_period_id` INT(11) NOT NULL COMMENT 'links to academic_periods.id',
  `land_type_id` INT(11) NOT NULL COMMENT 'links to land_types.id',
  `land_status_id` INT(11) NOT NULL COMMENT 'infrastructure_statuses.id',
  `infrastructure_ownership_id` INT(11) NOT NULL COMMENT 'links to infrastructure_ownerships.id',
  `infrastructure_condition_id` INT(11) NOT NULL COMMENT 'links to infrastructure_conditions.id',
  `previous_institution_land_id` INT(11) NULL COMMENT 'links to institution_lands.id',
  `original_id` INT(11) NOT NULL,
  `original_parent_id` INT(11) NULL,
  `modified_user_id` INT(11) NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  `created_user_id` INT(11) NOT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `code` (`code`),
  INDEX `institution_id` (`institution_id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `land_type_id` (`land_type_id`),
  INDEX `land_status_id` (`land_status_id`),
  INDEX `infrastructure_ownership_id` (`infrastructure_ownership_id`),
  INDEX `infrastructure_condition_id` (`infrastructure_condition_id`),
  INDEX `previous_institution_land_id` (`previous_institution_land_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;

-- land_types
CREATE TABLE `land_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_id` int(11) NOT NULL,
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
  KEY `created_user_id` (`created_user_id`))
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- institution_buildings
CREATE TABLE IF NOT EXISTS `institution_buildings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(100) NOT NULL,
  `name` VARCHAR(250) NOT NULL,
  `start_date` DATE NULL,
  `start_year` INT(4) NULL,
  `end_date` DATE NULL,
  `end_year` INT(4) NULL,
  `year_acquired` INT(4) NULL,
  `year_disposed` INT(4) NULL,
  `area` FLOAT NULL,
  `comment` TEXT NULL,
  `institution_land_id` INT(11) NULL COMMENT 'links to institution_lands.id',
  `institution_id` INT(11) NOT NULL COMMENT 'links to institutions.id',
  `academic_period_id` INT(11) NOT NULL COMMENT 'links to academic_periods.id',
  `building_type_id` INT(11) NOT NULL COMMENT 'links to building_types.id',
  `building_status_id` INT(11) NOT NULL COMMENT 'infrastructure_statuses.id',
  `infrastructure_ownership_id` INT(11) NOT NULL COMMENT 'links to infrastructure_ownerships.id',
  `infrastructure_condition_id` INT(11) NOT NULL COMMENT 'links to infrastructure_conditions.id',
  `previous_institution_building_id` INT(11) NULL COMMENT 'links to institution_buildings.id',
  `original_id` INT(11) NOT NULL,
  `original_parent_id` INT(11) NULL,
  `modified_user_id` INT(11) NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  `created_user_id` INT(11) NOT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `code` (`code`),
  INDEX `institution_land_id` (`institution_land_id`),
  INDEX `institution_id` (`institution_id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `building_type_id` (`building_type_id`),
  INDEX `building_status_id` (`building_status_id`),
  INDEX `infrastructure_ownership_id` (`infrastructure_ownership_id`),
  INDEX `infrastructure_condition_id` (`infrastructure_condition_id`),
  INDEX `previous_institution_building_id` (`previous_institution_building_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;

-- building_types
CREATE TABLE `building_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_id` int(11) NOT NULL,
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
  KEY `created_user_id` (`created_user_id`))
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- institution_floors
CREATE TABLE IF NOT EXISTS `institution_floors` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(100) NOT NULL,
  `name` VARCHAR(250) NOT NULL,
  `start_date` DATE NULL,
  `start_year` INT(4) NULL,
  `end_date` DATE NULL,
  `end_year` INT(4) NULL,
  `area` FLOAT NULL,
  `comment` TEXT NULL,
  `institution_building_id` INT(11) NULL COMMENT 'links to institution_buildings.id',
  `institution_id` INT(11) NOT NULL COMMENT 'links to institutions.id',
  `academic_period_id` INT(11) NOT NULL COMMENT 'links to academic_periods.id',
  `floor_type_id` INT(11) NULL COMMENT 'links to floor_types.id',
  `floor_status_id` INT(11) NOT NULL COMMENT 'infrastructure_statuses.id',
  `infrastructure_condition_id` INT(11) NOT NULL COMMENT 'links to infrastructure_conditions.id',
  `previous_institution_floor_id` INT(11) NULL COMMENT 'links to institution_floors.id',
  `original_id` INT(11) NOT NULL,
  `original_parent_id` INT(11) NULL,
  `modified_user_id` INT(11) NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  `created_user_id` INT(11) NOT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `code` (`code`),
  INDEX `institution_infrastructure_id` (`institution_building_id`),
  INDEX `institution_id` (`institution_id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `floor_type_id` (`floor_type_id`),
  INDEX `floor_status_id` (`floor_status_id`),
  INDEX `infrastructure_condition_id` (`infrastructure_condition_id`),
  INDEX `previous_institution_floor_id` (`previous_institution_floor_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;

-- floor_types
CREATE TABLE `floor_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_id` int(11) NOT NULL,
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
  KEY `created_user_id` (`created_user_id`))
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- institution_rooms
ALTER TABLE `institution_rooms`
CHANGE COLUMN `institution_infrastructure_id` `institution_floor_id` INT(11) NOT NULL COMMENT 'links to institution_floors.id' ,
CHANGE COLUMN `institution_id` `institution_id` INT(11) NOT NULL COMMENT 'links to institutions.id' ,
CHANGE COLUMN `academic_period_id` `academic_period_id` INT(11) NOT NULL COMMENT 'links to academic_periods.id' ,
CHANGE COLUMN `room_type_id` `room_type_id` INT(11) NOT NULL COMMENT 'links to infrastructure_type_id.id' ,
CHANGE COLUMN `infrastructure_condition_id` `infrastructure_condition_id` INT(11) NOT NULL COMMENT 'links to infrastructure_conditions.id',
CHANGE COLUMN `previous_room_id` `previous_institution_room_id` INT(11) NULL COMMENT 'links to institution_rooms.id';

UPDATE `institution_rooms`
SET `previous_institution_room_id` = NULL
WHERE `previous_institution_room_id` = 0;

-- patching institution_infrastructures to institution_lands / institution_buildings / institution_floors and land_types / building_types / floor_types
INSERT INTO institution_lands (
  `code`,
  `name`,
  `start_year`,
  `start_date`,
  `end_year`,
  `end_date`,
  `year_acquired`,
  `year_disposed`,
  `academic_period_id`,
  `area`,
  `comment`,
  `institution_id`,
  `land_type_id`,
  `land_status_id`,
  `infrastructure_ownership_id`,
  `infrastructure_condition_id`,
  `original_id`,
  `original_parent_id`,
  `created`,
  `created_user_id`
)
SELECT
  `II`.`code`,
  `II`.`name`,
  `A`.`start_year`,
  `A`.`start_date`,
  `A`.`end_year`,
  `A`.`end_date`,
  `II`.`year_acquired`,
  `II`.`year_disposed`,
  `A`.`id` as `academic_period_id`,
  `II`.`size`,
  `II`.`comment`,
  `II`.`institution_id`,
  `II`.`infrastructure_type_id`,
  `IS`.`id` as `land_status_id`,
  `II`.`infrastructure_ownership_id`,
  `II`.`infrastructure_condition_id`,
  `II`.`id`,
  `II`.`parent_id`,
  NOW(),
  `II`.`created_user_id`
FROM `institution_infrastructures` as `II`
INNER JOIN `infrastructure_levels` as `Levels`
  ON `Levels`.`id` = `II`.`infrastructure_level_id`
LEFT JOIN `infrastructure_statuses` `IS`
  ON `IS`.`code` = 'IN_USE'
LEFT JOIN `academic_periods` as `A`
  ON `A`.`current` = 1
WHERE `Levels`.`code` = 'LAND';

INSERT INTO land_types (
  `original_id`,
  `name`,
  `order`,
  `visible`,
  `editable`,
  `default`,
  `international_code`,
  `national_code`,
  `modified_user_id`,
  `modified`,
  `created_user_id`,
  `created`
)
SELECT
  `IT`.`id`,
  `IT`.`name`,
  `IT`.`order`,
  `IT`.`visible`,
  `IT`.`editable`,
  `IT`.`default`,
  `IT`.`international_code`,
  `IT`.`national_code`,
  `IT`.`modified_user_id`,
  `IT`.`modified`,
  `IT`.`created_user_id`,
  `IT`.`created`
FROM `infrastructure_types` `IT`
INNER JOIN `infrastructure_levels` `IL`
  ON `IL`.`id` = `IT`.`infrastructure_level_id`
WHERE `IL`.`code` = 'LAND';

UPDATE `institution_lands` `IL`
INNER JOIN `land_types` `LT` ON `IL`.`land_type_id` = `LT`.`original_id`
SET `IL`.`land_type_id` = `LT`.`id`;

INSERT INTO institution_buildings (
  `code`,
  `name`,
  `start_year`,
  `start_date`,
  `end_year`,
  `end_date`,
  `year_acquired`,
  `year_disposed`,
  `academic_period_id`,
  `area`,
  `comment`,
  `institution_id`,
  `building_status_id`,
  `building_type_id`,
  `infrastructure_ownership_id`,
  `infrastructure_condition_id`,
  `original_id`,
  `original_parent_id`,
  `created`,
  `created_user_id`
)
SELECT
  `II`.`code`,
  `II`.`name`,
  `A`.`start_year`,
  `A`.`start_date`,
  `A`.`end_year`,
  `A`.`end_date`,
  `II`.`year_acquired`,
  `II`.`year_disposed`,
  `A`.`id` as `academic_period_id`,
  `II`.`size`,
  `II`.`comment`,
  `II`.`institution_id`,
  `IS`.`id` as `building_status_id`,
  `II`.`infrastructure_type_id`,
  `II`.`infrastructure_ownership_id`,
  `II`.`infrastructure_condition_id`,
  `II`.`id`,
  `II`.`parent_id`,
  NOW(),
  `II`.`created_user_id`
FROM `institution_infrastructures` as `II`
INNER JOIN `infrastructure_levels` as `Levels`
  ON `Levels`.`id` = `II`.`infrastructure_level_id`
LEFT JOIN `infrastructure_statuses` `IS`
  ON `IS`.`code` = 'IN_USE'
LEFT JOIN `academic_periods` as `A`
  ON `A`.`current` = 1
WHERE `Levels`.`code` = 'BUILDING';

INSERT INTO building_types (
  `original_id`,
  `name`,
  `order`,
  `visible`,
  `editable`,
  `default`,
  `international_code`,
  `national_code`,
  `modified_user_id`,
  `modified`,
  `created_user_id`,
  `created`
)
SELECT
  `IT`.`id`,
  `IT`.`name`,
  `IT`.`order`,
  `IT`.`visible`,
  `IT`.`editable`,
  `IT`.`default`,
  `IT`.`international_code`,
  `IT`.`national_code`,
  `IT`.`modified_user_id`,
  `IT`.`modified`,
  `IT`.`created_user_id`,
  `IT`.`created`
FROM `infrastructure_types` `IT`
INNER JOIN `infrastructure_levels` `IL`
  ON `IL`.`id` = `IT`.`infrastructure_level_id`
WHERE `IL`.`code` = 'BUILDING';

UPDATE `institution_buildings` `IB`
INNER JOIN `building_types` `BT` ON `IB`.`building_type_id` = `BT`.`original_id`
SET `IB`.`building_type_id` = `BT`.`id`;

UPDATE `institution_buildings` `IB`
INNER JOIN `institution_lands` `IL` ON `IL`.`original_id` = `IB`.`original_parent_id`
SET `IB`.`institution_land_id` = `IL`.`id`;

INSERT INTO institution_floors (
  `code`,
  `name`,
  `start_year`,
  `start_date`,
  `end_year`,
  `end_date`,
  `academic_period_id`,
  `area`,
  `comment`,
  `institution_id`,
  `floor_status_id`,
  `floor_type_id`,
  `infrastructure_condition_id`,
  `original_id`,
  `original_parent_id`,
  `created`,
  `created_user_id`
)
SELECT
  `II`.`code`,
  `II`.`name`,
  `A`.`start_year`,
  `A`.`start_date`,
  `A`.`end_year`,
  `A`.`end_date`,
  `A`.`id` as `academic_period_id`,
  `II`.`size`,
  `II`.`comment`,
  `II`.`institution_id`,
  `IS`.`id` as `floor_status_id`,
  `II`.`infrastructure_type_id`,
  `II`.`infrastructure_condition_id`,
  `II`.`id`,
  `II`.`parent_id`,
  NOW(),
  `II`.`created_user_id`
FROM `institution_infrastructures` as `II`
INNER JOIN `infrastructure_levels` as `Levels`
  ON `Levels`.`id` = `II`.`infrastructure_level_id`
LEFT JOIN `infrastructure_statuses` `IS`
  ON `IS`.`code` = 'IN_USE'
LEFT JOIN `academic_periods` as `A`
  ON `A`.`current` = 1
WHERE `Levels`.`code` = 'FLOOR';

INSERT INTO floor_types (
  `original_id`,
  `name`,
  `order`,
  `visible`,
  `editable`,
  `default`,
  `international_code`,
  `national_code`,
  `modified_user_id`,
  `modified`,
  `created_user_id`,
  `created`
)
SELECT
  `IT`.`id`,
  `IT`.`name`,
  `IT`.`order`,
  `IT`.`visible`,
  `IT`.`editable`,
  `IT`.`default`,
  `IT`.`international_code`,
  `IT`.`national_code`,
  `IT`.`modified_user_id`,
  `IT`.`modified`,
  `IT`.`created_user_id`,
  `IT`.`created`
FROM `infrastructure_types` `IT`
INNER JOIN `infrastructure_levels` `IL`
  ON `IL`.`id` = `IT`.`infrastructure_level_id`
WHERE `IL`.`code` = 'FLOOR';

UPDATE `institution_floors` `IF`
INNER JOIN `institution_buildings` `IB` ON `IB`.`original_id` = `IF`.`original_parent_id`
SET `IF`.`institution_building_id` = `IB`.`id`;

UPDATE `institution_floors` `IF`
INNER JOIN `floor_types` `FT` ON `IF`.`floor_type_id` = `FT`.`original_id`
SET `IF`.`floor_type_id` = `FT`.`id`;

UPDATE `institution_rooms` `IR`
INNER JOIN `institution_floors` `IF` ON `IF`.`original_id` = `IR`.`institution_floor_id`
SET `IR`.`institution_floor_id` = `IF`.`id`;

-- custom_fields
CREATE TABLE `land_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `institution_land_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `infrastructure_custom_field_id` (`infrastructure_custom_field_id`),
  KEY `number_value` (`number_value`),
  KEY `institution_land_id` (`institution_land_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`))
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT INTO `land_custom_field_values` (
  `id`,
  `text_value`,
  `number_value`,
  `decimal_value`,
  `textarea_value`,
  `date_value`,
  `time_value`,
  `file`,
  `infrastructure_custom_field_id`,
  `institution_land_id`,
  `created_user_id`,
  `created`
)
SELECT
  `ICFV`.`id`,
  `ICFV`.`text_value`,
  `ICFV`.`number_value`,
  `ICFV`.`decimal_value`,
  `ICFV`.`textarea_value`,
  `ICFV`.`date_value`,
  `ICFV`.`time_value`,
  `ICFV`.`file`,
  `ICFV`.`infrastructure_custom_field_id`,
  `IL`.`id` as `institution_land_id`,
  `ICFV`.`created_user_id`,
  NOW()
FROM `infrastructure_custom_field_values` `ICFV`
INNER JOIN `institution_lands` `IL`
  ON `IL`.`original_id` = `ICFV`.`institution_infrastructure_id`;

CREATE TABLE `building_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `institution_building_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `infrastructure_custom_field_id` (`infrastructure_custom_field_id`),
  KEY `number_value` (`number_value`),
  KEY `institution_building_id` (`institution_building_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`))
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT INTO `building_custom_field_values` (
  `id`,
  `text_value`,
  `number_value`,
  `decimal_value`,
  `textarea_value`,
  `date_value`,
  `time_value`,
  `file`,
  `infrastructure_custom_field_id`,
  `institution_building_id`,
  `created_user_id`,
  `created`
)
SELECT
  `ICFV`.`id`,
  `ICFV`.`text_value`,
  `ICFV`.`number_value`,
  `ICFV`.`decimal_value`,
  `ICFV`.`textarea_value`,
  `ICFV`.`date_value`,
  `ICFV`.`time_value`,
  `ICFV`.`file`,
  `ICFV`.`infrastructure_custom_field_id`,
  `IB`.`id` as `institution_building_id`,
  `ICFV`.`created_user_id`,
  NOW()
FROM `infrastructure_custom_field_values` `ICFV`
INNER JOIN `institution_buildings` `IB`
  ON `IB`.`original_id` = `ICFV`.`institution_infrastructure_id`;

CREATE TABLE `floor_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `institution_floor_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `infrastructure_custom_field_id` (`infrastructure_custom_field_id`),
  KEY `number_value` (`number_value`),
  KEY `institution_floor_id` (`institution_floor_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`))
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT INTO `floor_custom_field_values` (
  `id`,
  `text_value`,
  `number_value`,
  `decimal_value`,
  `textarea_value`,
  `date_value`,
  `time_value`,
  `file`,
  `infrastructure_custom_field_id`,
  `institution_floor_id`,
  `created_user_id`,
  `created`
)
SELECT
  `ICFV`.`id`,
  `ICFV`.`text_value`,
  `ICFV`.`number_value`,
  `ICFV`.`decimal_value`,
  `ICFV`.`textarea_value`,
  `ICFV`.`date_value`,
  `ICFV`.`time_value`,
  `ICFV`.`file`,
  `ICFV`.`infrastructure_custom_field_id`,
  `IF`.`id` as `institution_floor_id`,
  `ICFV`.`created_user_id`,
  NOW()
FROM `infrastructure_custom_field_values` `ICFV`
INNER JOIN `institution_floors` `IF`
  ON `IF`.`original_id` = `ICFV`.`institution_infrastructure_id`;

CREATE TABLE z_3853_infrastructure_custom_forms LIKE infrastructure_custom_forms;

INSERT INTO z_3853_infrastructure_custom_forms
SELECT * FROM infrastructure_custom_forms;

ALTER TABLE `infrastructure_custom_forms`
ADD COLUMN `original_id` INT(11) NULL AFTER `created`,
ADD COLUMN `original_module_id` INT(11) NULL AFTER `original_id`;

UPDATE `custom_modules`
SET `id` = 10
WHERE `id` = 7;

UPDATE `infrastructure_custom_forms`
SET `custom_module_id` = 10
WHERE `custom_module_id` = 7;

INSERT INTO `custom_modules` (`id`, `code`, `name`, `model`, `visible`, `parent_id`, `created_user_id`, `created`) VALUES
(7, 'Land', 'Institution > Land', 'Institution.InstitutionLands', 1, 1, 1, NOW()),
(8, 'Building', 'Institution > Building', 'Institution.InstitutionBuildings', 1, 1, 1, NOW()),
(9, 'Floor', 'Institution > Floor', 'Institution.InstitutionFloors', 1, 1, 1, NOW());

-- Add new records to the general types
INSERT INTO `infrastructure_custom_forms` (
  `name`,
    `description`,
    `custom_module_id`,
    `created_user_id`,
    `created`,
    `original_id`,
    `original_module_id`
)
SELECT CONCAT(`CM2`.`code`, ' - General') as `name`,  `ICF`.`description`, `CM2`.`id` as `custom_module_id`, 1, NOW(), `ICF`.`id` as `original_id`, `ICF`.`custom_module_id` as `original_module_id`
FROM `infrastructure_custom_forms_filters` `ICFF`
INNER JOIN `infrastructure_custom_forms` `ICF`
  ON `ICFF`.`infrastructure_custom_form_id` = `ICF`.`id`
INNER JOIN `custom_modules` `CM`
  ON `CM`.`id` = `ICF`.`custom_module_id`
LEFT JOIN `custom_modules` `CM2`
  ON `CM2`.`id` IN (7, 8, 9)
WHERE `CM`.`code` = 'Infrastructure'
  AND `ICFF`.`infrastructure_custom_filter_id` = 0;

-- Patch infrastructure_custom_forms
INSERT INTO `infrastructure_custom_forms` (
  `name`,
    `description`,
    `custom_module_id`,
    `created_user_id`,
    `created`,
    `original_id`,
    `original_module_id`
)
SELECT
  CONCAT(`ICF`.`name`, ' - Land'),
    `ICF`.`description`, `CM2`.`id` as `custom_module_id`,
    1,
    NOW(),
    `ICF`.`id` as `original_id`,
    `ICF`.`custom_module_id` as `original_module_id`
FROM `infrastructure_custom_forms_filters` `ICFF`
INNER JOIN `infrastructure_custom_forms` `ICF`
  ON `ICFF`.`infrastructure_custom_form_id` = `ICF`.`id`
INNER JOIN `custom_modules` `CM`
  ON `CM`.`id` = `ICF`.`custom_module_id`
INNER JOIN `land_types` `LT`
  ON `LT`.`original_id` = `ICFF`.`infrastructure_custom_filter_id`
LEFT JOIN `custom_modules` `CM2`
  ON `CM2`.`id` = 7
WHERE `CM`.`code` = 'Infrastructure';

INSERT INTO `infrastructure_custom_forms` (
  `name`,
    `description`,
    `custom_module_id`,
    `created_user_id`,
    `created`,
    `original_id`,
    `original_module_id`
)
SELECT
  CONCAT(`ICF`.`name`, ' - Building'),
    `ICF`.`description`, `CM2`.`id` as `custom_module_id`,
    1,
    NOW(),
    `ICF`.`id` as `original_id`,
    `ICF`.`custom_module_id` as `original_module_id`
FROM `infrastructure_custom_forms_filters` `ICFF`
INNER JOIN `infrastructure_custom_forms` `ICF`
  ON `ICFF`.`infrastructure_custom_form_id` = `ICF`.`id`
INNER JOIN `custom_modules` `CM`
  ON `CM`.`id` = `ICF`.`custom_module_id`
INNER JOIN `building_types` `BT`
  ON `BT`.`original_id` = `ICFF`.`infrastructure_custom_filter_id`
LEFT JOIN `custom_modules` `CM2`
  ON `CM2`.`id` = 8
WHERE `CM`.`code` = 'Infrastructure';

INSERT INTO `infrastructure_custom_forms` (
  `name`,
    `description`,
    `custom_module_id`,
    `created_user_id`,
    `created`,
    `original_id`,
    `original_module_id`
)
SELECT
  CONCAT(`ICF`.`name`, ' - Floor'),
    `ICF`.`description`, `CM2`.`id` as `custom_module_id`,
    1,
    NOW(),
    `ICF`.`id` as `original_id`,
    `ICF`.`custom_module_id` as `original_module_id`
FROM `infrastructure_custom_forms_filters` `ICFF`
INNER JOIN `infrastructure_custom_forms` `ICF`
  ON `ICFF`.`infrastructure_custom_form_id` = `ICF`.`id`
INNER JOIN `custom_modules` `CM`
  ON `CM`.`id` = `ICF`.`custom_module_id`
INNER JOIN `floor_types` `FT`
  ON `FT`.`original_id` = `ICFF`.`infrastructure_custom_filter_id`
LEFT JOIN `custom_modules` `CM2`
  ON `CM2`.`id` = 9
WHERE `CM`.`code` = 'Infrastructure';

-- Patch infrastructure_custom_forms_fields

CREATE TABLE `z_3853_infrastructure_custom_forms_fields` LIKE `infrastructure_custom_forms_fields`;

INSERT INTO `z_3853_infrastructure_custom_forms_fields`
SELECT * FROM `infrastructure_custom_forms_fields`;

INSERT INTO infrastructure_custom_forms_fields (
  `id`,
    `infrastructure_custom_form_id`,
    `infrastructure_custom_field_id`,
    `section`,
    `name`,
    `is_mandatory`,
    `is_unique`,
    `order`
)
SELECT
  uuid() as `id`,
    `ICF`.`id` as `infrastructure_custom_form_id`,
    `ICFF`.`infrastructure_custom_field_id`,
    `ICFF`.`section`,
    `ICFF`.`name`,
    `ICFF`.`is_mandatory`,
    `ICFF`.`is_unique`,
    `ICFF`.`order`
FROM `infrastructure_custom_forms_fields` `ICFF`
INNER JOIN `infrastructure_custom_forms` `ICF`
  ON `ICF`.`original_id` = `ICFF`.`infrastructure_custom_form_id`;

-- Patch infrastructure_custom_forms_filters
ALTER TABLE `room_statuses`
RENAME TO `z_3853_room_statuses`;

CREATE TABLE `z_3853_infrastructure_custom_forms_filters` LIKE `infrastructure_custom_forms_filters`;

INSERT INTO `z_3853_infrastructure_custom_forms_filters`
SELECT * FROM `infrastructure_custom_forms_filters`;

INSERT INTO infrastructure_custom_forms_filters (
  `id`,
    `infrastructure_custom_form_id`,
    `infrastructure_custom_filter_id`
)
SELECT
  uuid() as `id`,
    `ICF`.`id` as `infrastructure_custom_form_id`,
    `ICFF`.`infrastructure_custom_filter_id`
FROM `infrastructure_custom_forms_filters` `ICFF`
INNER JOIN `infrastructure_custom_forms` `ICF`
  ON `ICFF`.`infrastructure_custom_form_id` = `ICF`.`original_id`;

-- Patch land custom filter
UPDATE `infrastructure_custom_forms_filters` `ICFF`
INNER JOIN `infrastructure_custom_forms` `ICF`
  ON `ICF`.`id` = `ICFF`.`infrastructure_custom_form_id`
INNER JOIN `custom_modules` `CF`
  ON `CF`.`id` = `ICF`.`custom_module_id`
    AND `CF`.`code` = 'Land'
INNER JOIN `land_types` `LT`
  ON `LT`.`original_id` = `ICFF`.`infrastructure_custom_filter_id`
SET `ICFF`.`infrastructure_custom_filter_id` = `LT`.`id`;

-- Patch building custom filter
UPDATE `infrastructure_custom_forms_filters` `ICFF`
INNER JOIN `infrastructure_custom_forms` `ICF`
  ON `ICF`.`id` = `ICFF`.`infrastructure_custom_form_id`
INNER JOIN `custom_modules` `CF`
  ON `CF`.`id` = `ICF`.`custom_module_id`
    AND `CF`.`code` = 'Building'
INNER JOIN `building_types` `BT`
  ON `BT`.`original_id` = `ICFF`.`infrastructure_custom_filter_id`
SET `ICFF`.`infrastructure_custom_filter_id` = `BT`.`id`;

-- Patch floor custom filter
UPDATE `infrastructure_custom_forms_filters` `ICFF`
INNER JOIN `infrastructure_custom_forms` `ICF`
  ON `ICF`.`id` = `ICFF`.`infrastructure_custom_form_id`
INNER JOIN `custom_modules` `CF`
  ON `CF`.`id` = `ICF`.`custom_module_id`
    AND `CF`.`code` = 'Floor'
INNER JOIN `floor_types` `FT`
  ON `FT`.`original_id` = `ICFF`.`infrastructure_custom_filter_id`
SET `ICFF`.`infrastructure_custom_filter_id` = `FT`.`id`;

-- clean up
DELETE FROM `custom_modules`
WHERE `id` = 4;

DELETE FROM `infrastructure_custom_forms_fields`
WHERE `infrastructure_custom_form_id` IN (
  SELECT DISTINCT `original_id` FROM `infrastructure_custom_forms`
);

DELETE FROM infrastructure_custom_forms
WHERE id IN (SELECT original_id FROM (
  SELECT DISTINCT original_id
    FROM infrastructure_custom_forms
) as tmp);

ALTER TABLE `institution_lands`
DROP COLUMN `original_parent_id`,
DROP COLUMN `original_id`;

ALTER TABLE `institution_buildings`
DROP COLUMN `original_parent_id`,
DROP COLUMN `original_id`,
CHANGE COLUMN `institution_land_id` `institution_land_id` INT(11) NOT NULL COMMENT 'links to institution_lands.id';

ALTER TABLE `institution_floors`
DROP COLUMN `original_parent_id`,
DROP COLUMN `original_id`,
CHANGE COLUMN `institution_building_id` `institution_building_id` INT(11) NOT NULL COMMENT 'links to institution_buildings.id';

ALTER TABLE `land_types`
DROP COLUMN `original_id`;

ALTER TABLE `building_types`
DROP COLUMN `original_id`;

ALTER TABLE `floor_types`
DROP COLUMN `original_id`;

ALTER TABLE `institution_infrastructures`
RENAME TO `z_3853_institution_infrastructures`;

ALTER TABLE `infrastructure_custom_forms`
DROP COLUMN `original_id`,
DROP COLUMN `original_module_id`;

ALTER TABLE `infrastructure_custom_field_values`
RENAME TO  `z_3853_infrastructure_custom_field_values` ;

ALTER TABLE `infrastructure_types`
RENAME TO  `z_3853_infrastructure_types` ;

-- Patch security function
UPDATE `security_functions`
SET
  `_view`='Fields.index|Fields.view|LandPages.index|LandPages.view|BuildingPages.index|BuildingPages.view|FloorPages.index|FloorPages.view|RoomPages.index|RoomPages.view|LandTypes.index|LandTypes.view|BuildingTypes.index|BuildingTypes.view|FloorTypes.index|FloorTypes.view|RoomTypes.index|RoomTypes.view',
  `_edit`='Fields.edit|LandPages.edit|BuildingPages.edit|FloorPages.edit|RoomPages.edit|LandTypes.edit|BuildingTypes.edit|FloorTypes.edit|RoomTypes.edit',
  `_add`='Fields.add|LandPages.add|BuildingPages.add|FloorPages.add|RoomPages.add|LandTypes.add|BuildingTypes.add|FloorTypes.add|RoomTypes.add',
  `_delete`='Fields.remove|LandPages.remove|BuildingPages.remove|FloorPages.remove|RoomPages.remove|LandTypes.remove|BuildingTypes.remove|FloorTypes.remove|RoomTypes.remove'
WHERE `id`='5018';

UPDATE `security_functions`
SET
  `_view`='InstitutionLands.index|InstitutionLands.view|InstitutionBuildings.index|InstitutionBuildings.view|InstitutionFloors.index|InstitutionFloors.view|InstitutionRooms.index|InstitutionRooms.view',
  `_edit`='InstitutionLands.edit|InstitutionBuildings.edit|InstitutionFloors.edit|InstitutionRooms.edit',
  `_add`='InstitutionLands.add|InstitutionBuildings.add|InstitutionFloors.add|InstitutionRooms.add',
  `_delete`='InstitutionLands.remove|InstitutionBuildings.remove|InstitutionFloors.remove|InstitutionRooms.remove'
WHERE `id`='1011';

UPDATE `security_functions`
SET
  `_view`='Institutions.index|Institutions.view|dashboard',
  `_edit`='Institutions.edit',
  `_add`='Institutions.add',
  `_delete`='Institutions.remove',
  `_execute`='Institutions.excel'
WHERE `id`='1000';


-- POCOR-3905
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3905', NOW());

-- education_grades
RENAME TABLE `education_grades` TO `z_3905_education_grades`;

DROP TABLE IF EXISTS `education_grades`;
CREATE TABLE IF NOT EXISTS `education_grades` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(50) NOT NULL,
    `name` varchar(150) NOT NULL,
    `admission_age` int(3) NOT NULL,
    `order` int(3) NOT NULL,
    `visible` int(1) NOT NULL DEFAULT '1',
    `education_programme_id` int(11) NOT NULL COMMENT 'links to education_programmes.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `education_programme_id` (`education_programme_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of education grades linked to specific education programmes';

-- insert data to the new table
INSERT INTO `education_grades` (`id`, `code`, `name`, `admission_age`, `order`, `visible`, `education_programme_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `grades`.`id`, `grades`.`code`, `grades`.`name`, `cycles`.`admission_age`, `grades`.`order`, `grades`.`visible`, `grades`.`education_programme_id`, `grades`.`modified_user_id`, `grades`.`modified`, `grades`.`created_user_id`, `grades`.`created`
FROM `z_3905_education_grades` AS `grades`
LEFT JOIN `education_programmes` AS `programmes`
    ON `programmes`.`id` = `grades`.`education_programme_id`
LEFT JOIN `education_cycles` AS `cycles`
    ON `cycles`.`id` = `programmes`.`education_cycle_id`;

-- to update the order and admission age
SET @order:=0;
SET @pid:=0;

UPDATE education_grades g,
(SELECT @order:= IF(@pid = `education_programme_id`, @order:=@order+1, @order:=1) AS NEWORDER,
        @pid:= `education_programme_id`,
        `id`
        FROM education_grades
        ORDER BY `education_programme_id`
) AS s
set g.`order` = s.`NEWORDER`,
    g.`admission_age` = g.`admission_age` + s.`NEWORDER` - 1
where g.`id` = s.`id`;


-- 3.9.14
UPDATE config_items SET value = '3.9.14' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
