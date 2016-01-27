--
-- POCOR-2506
--

DROP TABLE IF EXISTS `staff_position_titles`;

UPDATE `field_option_values` as `fov` set `fov`.`visible`=1 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'StaffPositionTitles'); 

DROP TABLE IF EXISTS `institution_positions`;
ALTER TABLE `z_2506_institution_positions` RENAME `institution_positions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2506';