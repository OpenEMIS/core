-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2078');

-- staff_leaves
ALTER TABLE `staff_leaves` CHANGE `leave_status_id` `status_id` INT(11) NOT NULL;

-- workflow_models
UPDATE `workflow_models` SET `name` = 'Staff > Career > Leave' WHERE `model` = 'Staff.Leaves';

-- workflow_steps
ALTER TABLE `workflow_steps` ADD `is_editable` INT(1) NOT NULL DEFAULT '0' AFTER `stage`, ADD `is_removable` INT(1) NOT NULL DEFAULT '0' AFTER `is_editable`;

-- workflow_actions
ALTER TABLE `workflow_actions` ADD `event_key` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `next_workflow_step_id`;

-- workflow_transitions
ALTER TABLE `workflow_transitions` CHANGE `comment` `comment` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- add INDEX
ALTER TABLE `workflows` ADD INDEX(`workflow_model_id`);
ALTER TABLE `workflow_steps` ADD INDEX(`workflow_id`);
ALTER TABLE `workflow_actions` ADD INDEX(`next_workflow_step_id`), ADD INDEX(`workflow_step_id`);
ALTER TABLE `workflow_records` ADD INDEX(`model_reference`), ADD INDEX(`workflow_model_id`), ADD INDEX(`workflow_step_id`);
ALTER TABLE `workflow_transitions` ADD INDEX(`prev_workflow_step_id`), ADD INDEX(`workflow_step_id`), ADD INDEX(`workflow_action_id`), ADD INDEX(`workflow_record_id`);

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'WorkflowSteps', 'is_editable', 'Workflow -> Steps', 'Editable', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'WorkflowSteps', 'is_removable', 'Workflow -> Steps', 'Removable', 1, 1, NOW());
