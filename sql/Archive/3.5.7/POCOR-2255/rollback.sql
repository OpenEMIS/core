--
-- POCOR-2255
--
ALTER TABLE `institution_fee_types` CHANGE `amount` `amount` DECIMAL(11,2) NOT NULL;
ALTER TABLE `institution_fees` CHANGE `total` `total` DECIMAL(20,2) NULL DEFAULT NULL;
ALTER TABLE `student_fees` CHANGE `amount` `amount` DECIMAL(11,2) NOT NULL;

DROP TABLE IF EXISTS `fee_types`;
UPDATE `field_option_values` as `fov` set `fov`.`visible`=1 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'FeeTypes'); 

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2255';