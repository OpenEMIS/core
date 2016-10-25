DROP TABLE `staff_training_applications`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3449';
