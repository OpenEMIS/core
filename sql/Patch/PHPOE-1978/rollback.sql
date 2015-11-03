-- Drop New tables
DROP TABLE IF EXISTS `staff_training_needs`;
DROP TABLE IF EXISTS `staff_training_self_studies`;

-- Restore Admin - training tables
RENAME TABLE `z_1978_staff_training_needs` TO `staff_training_needs`;
RENAME TABLE `z_1978_staff_training_self_studies` TO `staff_training_self_studies`;
RENAME TABLE `z_1978_staff_training_self_study_attachments` TO `staff_training_self_study_attachments`;
RENAME TABLE `z_1978_staff_training_self_study_results` TO `staff_training_self_study_results`;

-- labels
DELETE FROM `labels` WHERE `module` = 'Achievements' AND `field` = 'file_content';

-- workflow_models
DELETE FROM `workflow_models` WHERE `model` = 'Staff.TrainingNeeds';
DELETE FROM `workflow_models` WHERE `model` = 'Staff.Achievements';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1978';
