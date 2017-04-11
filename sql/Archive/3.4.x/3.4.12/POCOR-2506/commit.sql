--
-- POCOR-2506
--

-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2506', NOW());

DROP TABLE IF EXISTS `staff_position_titles`;
CREATE TABLE `staff_position_titles` LIKE `institution_network_connectivities`;
ALTER TABLE `staff_position_titles` ADD COLUMN `type` INT(1) NOT NULL COMMENT '0-Non-Teaching / 1-Teaching' AFTER `name`;
INSERT INTO `staff_position_titles`
SELECT 	
	`fov`.`id` as `id`,
	`fov`.`name` as `name`,
	'0' as `type`,
	`fov`.`order` as `order`,
	`fov`.`visible` as `visible`,
	`fov`.`editable` as `editable`,
	`fov`.`default` as `default`,
	`fov`.`international_code` as `international_code`,
	`fov`.`national_code` as `national_code`,
	`fov`.`modified_user_id` as `modified_user_id`,
	`fov`.`modified` as `modified`,
	`fov`.`created_user_id` as `created_user_id`,
	`fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'StaffPositionTitles'); 

UPDATE `staff_position_titles`
SET `type`=1
WHERE `staff_position_titles`.`id` IN (SELECT `ip`.`staff_position_title_id` from `institution_positions` as `ip` WHERE `ip`.`type`=1 group by `ip`.`staff_position_title_id`); 

UPDATE `field_option_values` as `fov` set `fov`.`visible`=0 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'StaffPositionTitles'); 

CREATE TABLE `z_2506_institution_positions` LIKE `institution_positions`;
ALTER TABLE `institution_positions` DROP COLUMN `type`;

UPDATE `security_functions` SET `_delete` = 'remove|transfer' WHERE `security_functions`.`id` = 5013;

DROP TABLE IF EXISTS `institution_genders`;
CREATE TABLE `institution_genders` LIKE `institution_network_connectivities`;
INSERT INTO `institution_genders`
SELECT 	
	`fov`.`id` as `id`,
	`fov`.`name` as `name`,
	`fov`.`order` as `order`,
	`fov`.`visible` as `visible`,
	`fov`.`editable` as `editable`,
	`fov`.`default` as `default`,
	`fov`.`international_code` as `international_code`,
	`fov`.`national_code` as `national_code`,
	`fov`.`modified_user_id` as `modified_user_id`,
	`fov`.`modified` as `modified`,
	`fov`.`created_user_id` as `created_user_id`,
	`fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Genders'); 

UPDATE `field_option_values` as `fov` set `fov`.`visible`=0 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Genders'); 

DROP TABLE IF EXISTS `institution_localities`;
CREATE TABLE `institution_localities` LIKE `institution_network_connectivities`;
INSERT INTO `institution_localities`
SELECT 	
	`fov`.`id` as `id`,
	`fov`.`name` as `name`,
	`fov`.`order` as `order`,
	`fov`.`visible` as `visible`,
	`fov`.`editable` as `editable`,
	`fov`.`default` as `default`,
	`fov`.`international_code` as `international_code`,
	`fov`.`national_code` as `national_code`,
	`fov`.`modified_user_id` as `modified_user_id`,
	`fov`.`modified` as `modified`,
	`fov`.`created_user_id` as `created_user_id`,
	`fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Localities'); 

UPDATE `field_option_values` as `fov` set `fov`.`visible`=0 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Localities'); 

DROP TABLE IF EXISTS `institution_ownerships`;
CREATE TABLE `institution_ownerships` LIKE `institution_network_connectivities`;
INSERT INTO `institution_ownerships`
SELECT 	
	`fov`.`id` as `id`,
	`fov`.`name` as `name`,
	`fov`.`order` as `order`,
	`fov`.`visible` as `visible`,
	`fov`.`editable` as `editable`,
	`fov`.`default` as `default`,
	`fov`.`international_code` as `international_code`,
	`fov`.`national_code` as `national_code`,
	`fov`.`modified_user_id` as `modified_user_id`,
	`fov`.`modified` as `modified`,
	`fov`.`created_user_id` as `created_user_id`,
	`fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Ownerships'); 

UPDATE `field_option_values` as `fov` set `fov`.`visible`=0 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Ownerships'); 

DROP TABLE IF EXISTS `institution_providers`;
CREATE TABLE `institution_providers` LIKE `institution_network_connectivities`;
INSERT INTO `institution_providers`
SELECT 	
	`fov`.`id` as `id`,
	`fov`.`name` as `name`,
	`fov`.`order` as `order`,
	`fov`.`visible` as `visible`,
	`fov`.`editable` as `editable`,
	`fov`.`default` as `default`,
	`fov`.`international_code` as `international_code`,
	`fov`.`national_code` as `national_code`,
	`fov`.`modified_user_id` as `modified_user_id`,
	`fov`.`modified` as `modified`,
	`fov`.`created_user_id` as `created_user_id`,
	`fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Providers'); 

UPDATE `field_option_values` as `fov` set `fov`.`visible`=0 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Providers'); 

DROP TABLE IF EXISTS `institution_sectors`;
CREATE TABLE `institution_sectors` LIKE `institution_network_connectivities`;
INSERT INTO `institution_sectors`
SELECT 	
	`fov`.`id` as `id`,
	`fov`.`name` as `name`,
	`fov`.`order` as `order`,
	`fov`.`visible` as `visible`,
	`fov`.`editable` as `editable`,
	`fov`.`default` as `default`,
	`fov`.`international_code` as `international_code`,
	`fov`.`national_code` as `national_code`,
	`fov`.`modified_user_id` as `modified_user_id`,
	`fov`.`modified` as `modified`,
	`fov`.`created_user_id` as `created_user_id`,
	`fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Sectors'); 

UPDATE `field_option_values` as `fov` set `fov`.`visible`=0 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Sectors'); 

DROP TABLE IF EXISTS `institution_statuses`;
CREATE TABLE `institution_statuses` LIKE `institution_network_connectivities`;
INSERT INTO `institution_statuses`
SELECT 	
	`fov`.`id` as `id`,
	`fov`.`name` as `name`,
	`fov`.`order` as `order`,
	`fov`.`visible` as `visible`,
	`fov`.`editable` as `editable`,
	`fov`.`default` as `default`,
	`fov`.`international_code` as `international_code`,
	`fov`.`national_code` as `national_code`,
	`fov`.`modified_user_id` as `modified_user_id`,
	`fov`.`modified` as `modified`,
	`fov`.`created_user_id` as `created_user_id`,
	`fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Statuses'); 

UPDATE `field_option_values` as `fov` set `fov`.`visible`=0 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Statuses'); 

DROP TABLE IF EXISTS `institution_types`;
CREATE TABLE `institution_types` LIKE `institution_network_connectivities`;
INSERT INTO `institution_types`
SELECT 	
	`fov`.`id` as `id`,
	`fov`.`name` as `name`,
	`fov`.`order` as `order`,
	`fov`.`visible` as `visible`,
	`fov`.`editable` as `editable`,
	`fov`.`default` as `default`,
	`fov`.`international_code` as `international_code`,
	`fov`.`national_code` as `national_code`,
	`fov`.`modified_user_id` as `modified_user_id`,
	`fov`.`modified` as `modified`,
	`fov`.`created_user_id` as `created_user_id`,
	`fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Types'); 

UPDATE `field_option_values` as `fov` set `fov`.`visible`=0 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'Types'); 

