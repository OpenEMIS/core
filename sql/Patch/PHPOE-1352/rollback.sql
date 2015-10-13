-- workflow_models
UPDATE `workflow_models` SET `name` = 'Staff Leave' WHERE `model` = 'Staff.Leaves';
DELETE FROM `workflow_models` WHERE `model` = 'Institution.InstitutionSurveys';

-- workflow_steps
ALTER TABLE `workflow_steps` DROP `is_removable`, DROP `is_editable`;

-- workflow_actions
ALTER TABLE `workflow_actions` DROP `event_key`;

-- workflow_transitions
ALTER TABLE `workflow_transitions` CHANGE `comment` `comment` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

-- drop INDEX
ALTER TABLE `workflows` DROP INDEX `workflow_model_id`;
ALTER TABLE `workflow_steps` DROP INDEX `workflow_id`;
ALTER TABLE `workflow_actions` DROP INDEX `next_workflow_step_id`, DROP INDEX `workflow_step_id`;
ALTER TABLE `workflow_records` DROP INDEX `model_reference`, DROP INDEX `workflow_model_id`, DROP INDEX `workflow_step_id`;
ALTER TABLE `workflow_transitions` DROP INDEX `prev_workflow_step_id`, DROP INDEX `workflow_step_id`, DROP INDEX `workflow_action_id`, DROP INDEX `workflow_record_id`;

-- labels
DELETE FROM `labels` WHERE `module` = 'WorkflowSteps' AND `field` = 'is_editable';
DELETE FROM `labels` WHERE `module` = 'WorkflowSteps' AND `field` = 'is_removable';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1352';
