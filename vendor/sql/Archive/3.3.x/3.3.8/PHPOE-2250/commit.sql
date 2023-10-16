-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2250', NOW());

-- Backup table
CREATE TABLE `z_2250_workflow_transitions` LIKE `workflow_transitions`;
INSERT INTO `z_2250_workflow_transitions` SELECT * FROM `workflow_transitions` WHERE 1;

-- Alter table - add colomn
ALTER TABLE `workflow_transitions` ADD `prev_workflow_step_name` VARCHAR(100) NOT NULL AFTER `prev_workflow_step_id`;
ALTER TABLE `workflow_transitions` ADD `workflow_step_name` VARCHAR(100) NOT NULL AFTER `workflow_step_id`;
ALTER TABLE `workflow_transitions` ADD `workflow_action_name` VARCHAR(100) NOT NULL AFTER `workflow_action_id`;

-- Update table
UPDATE `workflow_transitions` AS `WorkflowTransitions`
INNER JOIN `workflow_steps` AS `PrevWorkflowSteps`
ON `PrevWorkflowSteps`.`id` = `WorkflowTransitions`.`prev_workflow_step_id`
INNER JOIN `workflow_steps` AS `WorkflowSteps`
ON `WorkflowSteps`.`id` = `WorkflowTransitions`.`workflow_step_id`
INNER JOIN `workflow_actions` AS `WorkflowActions`
ON `WorkflowActions`.`id` = `WorkflowTransitions`.`workflow_action_id`
SET `WorkflowTransitions`.`prev_workflow_step_name` = `PrevWorkflowSteps`.`name`,
`WorkflowTransitions`.`workflow_step_name` = `WorkflowSteps`.`name`,
`WorkflowTransitions`.`workflow_action_name` = `WorkflowActions`.`name`;

-- Alter table - drop colomn
ALTER TABLE `workflow_transitions` DROP `prev_workflow_step_id`;
ALTER TABLE `workflow_transitions` DROP `workflow_step_id`;
ALTER TABLE `workflow_transitions` DROP `workflow_action_id`;
