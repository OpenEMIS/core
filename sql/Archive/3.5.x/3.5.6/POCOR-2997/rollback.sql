-- delete new table
DROP TABLE `staff_training_needs`;

-- rename back the backup table
ALTER TABLE `z_2997_staff_training_needs` 
RENAME TO  `staff_training_needs` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2997';