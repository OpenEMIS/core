-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1352');

-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `filter`, `created_user_id`, `created`) VALUES
('Institution - Survey', 'Institution.InstitutionSurveys', NULL, 1, '0000-00-00 00:00:00');

-- workflow_steps
ALTER TABLE `workflow_steps` ADD `is_editable` INT(1) NOT NULL DEFAULT '0' AFTER `stage`, ADD `is_removable` INT(1) NOT NULL DEFAULT '0' AFTER `is_editable`;

-- workflow_actions
ALTER TABLE `workflow_actions` ADD `event_key` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `next_workflow_step_id`;

-- workflow_records
ALTER TABLE `workflow_records` ADD INDEX(`model_reference`), ADD INDEX(`workflow_model_id`), ADD INDEX(`workflow_step_id`);

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'WorkflowSteps', 'is_editable', 'Workflow -> Steps', 'Editable', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'WorkflowSteps', 'is_removable', 'Workflow -> Steps', 'Removable', 1, 1, NOW());
