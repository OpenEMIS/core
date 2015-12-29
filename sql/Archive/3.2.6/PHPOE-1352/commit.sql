-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1352', NOW());

-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `filter`, `created_user_id`, `created`) VALUES
('Institutions > Survey > Forms', 'Institution.InstitutionSurveys', 'Survey.SurveyForms', 1, '0000-00-00 00:00:00');

-- workflow_steps
ALTER TABLE `workflow_steps` CHANGE `stage` `stage` INT(1) NULL DEFAULT NULL COMMENT '0 -> Open, 1 -> Pending For Approval, 2 -> Closed';

-- workflow_transitions
ALTER TABLE `workflow_transitions` CHANGE `comment` `comment` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

-- staff_leaves
ALTER TABLE `staff_leaves` CHANGE `status_id` `status_id` INT(11) NOT NULL COMMENT 'links to workflow_steps.id';

-- institution_site_surveys
ALTER TABLE `institution_site_surveys` CHANGE `status` `status_id` INT(11) NOT NULL COMMENT 'links to workflow_steps.id';

-- Backup institution_site_surveys
CREATE TABLE `z_1352_institution_site_surveys` LIKE  `institution_site_surveys`;
INSERT INTO `z_1352_institution_site_surveys` SELECT * FROM `institution_site_surveys` WHERE 1;

-- Pre-insert workflows
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Institution.InstitutionSurveys';
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('SURVEY-1001', 'Institutions - Survey - General', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'SURVEY-1001';
INSERT INTO `workflows_filters` (`id`, `workflow_id`, `filter_id`) VALUES
(uuid(), @workflowId, 0);

-- Pre-insert workflow_steps
SET @openStatusId := 0;
SET @pendingStatusId := 0;
SET @closedStatusId := 0;
INSERT INTO `workflow_steps` (`name`, `stage`, `is_editable`, `is_removable`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 0, 1, 1, @workflowId, 1, NOW()),
('Pending For Approval', 1, 0, 0, @workflowId, 1, NOW()),
('Closed', 2, 0, 0, @workflowId, 1, NOW());

SELECT `id` INTO @openStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `stage` = 0;
SELECT `id` INTO @pendingStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `stage` = 1;
SELECT `id` INTO @closedStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `stage` = 2;

-- Pre-insert workflow_actions
INSERT INTO `workflow_actions` (`name`, `action`, `visible`, `next_workflow_step_id`, `event_key`, `comment_required`, `workflow_step_id`, `created_user_id`, `created`) VALUES
('Submit For Approval', 0, 1, @pendingStatusId, NULL, 0, @openStatusId, 1, NOW()),
('Cancel', 1, 1, @closedStatusId, NULL, 0, @openStatusId, 1, NOW()),
('Approve', 0, 1, @closedStatusId, NULL, 0, @pendingStatusId, 1, NOW()),
('Reject', 1, 1, @openStatusId, NULL, 0, @pendingStatusId, 1, NOW()),
('Approve', 0, 0, 0, NULL, 0, @closedStatusId, 1, NOW()),
('Reject', 1, 0, 0, NULL, 0, @closedStatusId, 1, NOW()),
('Reopen', NULL, 1, @openStatusId, NULL, 0, @closedStatusId, 1, NOW());

-- Update status_id of institution_site_surveys
UPDATE `institution_site_surveys` SET `status_id` = @openStatusId WHERE `status_id` IN (0, 1);
UPDATE `institution_site_surveys` SET `status_id` = @closedStatusId WHERE `status_id` = 2;

-- Pre-insert workflow_records
INSERT INTO `workflow_records` (`model_reference`, `workflow_model_id`, `workflow_step_id`, `created_user_id`, `created`)
SELECT `id`, @modelId, `status_id`, 1, NOW() FROM `institution_site_surveys` WHERE `status_id` <> -1 ORDER BY `id`;

-- Pre-insert workflow_transitions
SET @openActionId := 0;
SET @pendingActionId := 0;
SELECT `id` INTO @openActionId FROM `workflow_actions` WHERE `workflow_step_id` = @openStatusId AND `action` = 0;
SELECT `id` INTO @pendingActionId FROM `workflow_actions` WHERE `workflow_step_id` = @pendingStatusId AND `action` = 0;

-- Open to Pending
INSERT INTO `workflow_transitions` (`prev_workflow_step_id`, `workflow_step_id`, `workflow_action_id`, `workflow_record_id`, `created_user_id`, `created`)
SELECT @openStatusId, @pendingStatusId, @openActionId, `id`, 1, NOW() FROM `workflow_records` WHERE `workflow_model_id` = @modelId AND `workflow_step_id` = @closedStatusId ORDER BY `model_reference`;

-- Pending to Closed
INSERT INTO `workflow_transitions` (`prev_workflow_step_id`, `workflow_step_id`, `workflow_action_id`, `workflow_record_id`, `created_user_id`, `created`)
SELECT @pendingStatusId, @closedStatusId, @pendingActionId, `id`, 1, NOW() FROM `workflow_records` WHERE `workflow_model_id` = @modelId AND `workflow_step_id` = @closedStatusId ORDER BY `model_reference`;
