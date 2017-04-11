-- Drop New tables
DROP TABLE IF EXISTS `staff_training_needs`;

-- Restore Admin - training tables
RENAME TABLE `z_1978_staff_training_needs` TO `staff_training_needs`;

-- workflow_models
DELETE FROM `workflow_models` WHERE `model` = 'Staff.TrainingNeeds';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1978';
