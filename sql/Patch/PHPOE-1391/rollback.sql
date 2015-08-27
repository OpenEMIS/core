-- workflow_models
UPDATE `workflow_models` SET `model` = 'StaffLeave' WHERE `model` = 'Staff.Leaves';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1391';
