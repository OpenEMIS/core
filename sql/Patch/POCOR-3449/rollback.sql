-- drop table
DROP TABLE `staff_training_applications`;

-- delete workflow_models
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Institution.StaffTrainingApplications' AND `name` = 'Staff > Training > Applications';
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

-- DELETE FROM `workflow_statuses_steps` WHERE `workflow_status_id` IN (
--   SELECT `id` FROM `workflow_statuses` WHERE `workflow_model_id` = @modelId
-- );

-- DELETE FROM `workflow_statuses` WHERE `workflow_model_id` = @modelId;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3449';
