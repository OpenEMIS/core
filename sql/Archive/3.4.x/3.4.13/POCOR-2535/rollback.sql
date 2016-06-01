-- revert move out staff_position_grades from field_options
DROP TABLE IF EXISTS `staff_position_grades`;
UPDATE `field_options` SET `params` = NULL WHERE `code` = 'StaffPositionGrades';
UPDATE `field_option_values` SET `visible` = 1 WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'StaffPositionGrades');

-- delete pre-insert workflow
DELETE FROM `workflow_models` WHERE `model` = 'Institution.InstitutionPositions';
DELETE FROM `workflows` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflows`.`workflow_model_id`);
DELETE FROM `workflow_steps` WHERE NOT EXISTS (SELECT 1 FROM `workflows` WHERE `workflows`.`id` = `workflow_steps`.`workflow_id`);
DELETE FROM `workflow_actions` WHERE NOT EXISTS (SELECT 1 FROM `workflow_steps` WHERE `workflow_steps`.`id` = `workflow_actions`.`workflow_step_id`);

DELETE FROM `workflow_statuses` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflow_statuses`.`workflow_model_id`);
DELETE FROM `workflow_statuses_steps` WHERE NOT EXISTS (SELECT 1 FROM `workflow_statuses` WHERE `workflow_statuses`.`id` = `workflow_statuses_steps`.`workflow_status_id`);

DELETE FROM `workflow_records` WHERE NOT EXISTS (SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflow_records`.`workflow_model_id`);
DELETE FROM `workflow_transitions` WHERE NOT EXISTS (SELECT 1 FROM `workflow_records` WHERE `workflow_records`.`id` = `workflow_transitions`.`workflow_record_id`);

-- Restore table
DROP TABLE `institution_positions`;
RENAME TABLE `z_2535_institution_positions` TO `institution_positions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2535';
