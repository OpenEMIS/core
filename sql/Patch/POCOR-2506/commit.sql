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
	CASE `fov`.`name` 
		WHEN 'Principal' THEN '1'
		WHEN 'Vice Principal' THEN '1'
		WHEN 'Teacher' THEN '1'
		WHEN 'Assistant Lecturer' THEN '1'
		WHEN 'Assistant Teacher' THEN '1'
		WHEN 'First Teacher' THEN '1'
		WHEN 'Instructor' THEN '1'
		WHEN 'Itinerant Teacher' THEN '1'
		WHEN 'Lecturer' THEN '1'
		WHEN 'Lecturer/Supervisor' THEN '1'
		WHEN 'Public Educator/Trainer' THEN '1'
		ELSE '0' 
	END as `type`,
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

UPDATE `field_option_values` as `fov` set `fov`.`visible`=0 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'StaffPositionTitles'); 

CREATE TABLE `z_2506_institution_positions` LIKE `institution_positions`;
ALTER TABLE `institution_positions` DROP COLUMN `type`;

