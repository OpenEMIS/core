--
-- POCOR-2255
--
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2255', NOW());

ALTER TABLE `institution_fee_types` CHANGE `amount` `amount` DECIMAL(15,2) NOT NULL;
ALTER TABLE `institution_fees` CHANGE `total` `total` DECIMAL(50,2) NULL DEFAULT NULL;
ALTER TABLE `student_fees` CHANGE `amount` `amount` DECIMAL(50,2) NOT NULL;

DROP TABLE IF EXISTS `fee_types`;
CREATE TABLE `fee_types` LIKE `institution_network_connectivities`;
INSERT INTO `fee_types`
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
WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'FeeTypes');
UPDATE `field_option_values` as `fov` set `fov`.`visible`=0 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'FeeTypes'); 

