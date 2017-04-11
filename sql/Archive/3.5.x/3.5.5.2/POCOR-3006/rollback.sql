-- update back the position_no value
UPDATE `institution_positions`
INNER JOIN `z_3006_institution_positions` ON `institution_positions`.`id` = `z_3006_institution_positions`.`id`
SET `institution_positions`.`position_no` = `z_3006_institution_positions`.`position_no`;

-- remove backup table
DROP TABLE IF EXISTS `z_3006_institution_positions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3006';