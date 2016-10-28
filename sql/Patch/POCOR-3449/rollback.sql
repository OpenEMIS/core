-- drop table
DROP TABLE `staff_training_applications`;

-- delete workflow_models
SET @modelId := 10;
DELETE FROM `workflow_models` WHERE `id` = @modelId;

-- delete workflows
SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'TRN-5001' AND `workflow_model_id` = @modelId;
DELETE FROM `workflows` WHERE `id` = @workflowId;

-- delete workflow_actions
DELETE FROM `workflow_actions` WHERE `workflow_actions`.`workflow_step_id` IN (
  SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = @workflowId
);

-- delete workflow_steps
DELETE FROM `workflow_steps` WHERE `workflow_id` = @workflowId;

-- delete workflow_statuses_steps
DELETE FROM `workflow_statuses_steps` WHERE `workflow_status_id` IN (
  SELECT `id` FROM `workflow_statuses` WHERE `workflow_model_id` = @modelId
);

-- delete workflow_statuses
DELETE FROM `workflow_statuses` WHERE `workflow_model_id` = @modelId;

-- delete security_functions
DELETE FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Applications';

-- revert staff training security_functions
UPDATE `security_functions`
SET `controller` = 'Staff', `_view` = 'TrainingNeeds.index|TrainingNeeds.view', `_edit` = 'TrainingNeeds.edit', `_add` = 'TrainingNeeds.add', `_delete` = 'TrainingNeeds.remove'
WHERE `name` = 'Needs' AND `category` = 'Staff - Training';

UPDATE `security_functions`
SET `controller` = 'Staff', `_view` = 'TrainingResults.index|TrainingResults.view'
WHERE `name` = 'Results' AND `category` = 'Staff - Training';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3449';
