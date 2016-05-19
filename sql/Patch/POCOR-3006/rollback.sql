-- Restore table
DROP TABLE IF EXISTS `institution_positions`;

ALTER TABLE `z_3006_institution_positions` 
RENAME TO  `institution_positions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3006';