-- staff_training_needs
DROP TABLE `staff_training_needs`;
ALTER TABLE `z_2335_staff_training_needs` RENAME `staff_training_needs`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2335';
