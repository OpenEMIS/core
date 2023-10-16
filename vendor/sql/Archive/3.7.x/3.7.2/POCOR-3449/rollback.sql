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

-- delete workflow_steps_roles
DELETE FROM `workflow_steps_roles` WHERE `workflow_steps_roles`.`workflow_step_id` IN (
  SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = @workflowId
);

-- delete workflow_statuses_steps
DELETE FROM `workflow_statuses_steps` WHERE `workflow_status_id` IN (
  SELECT `id` FROM `workflow_statuses` WHERE `workflow_model_id` = @modelId
);

-- delete workflow_steps
DELETE FROM `workflow_steps` WHERE `workflow_id` = @workflowId;

-- delete workflow_statuses
DELETE FROM `workflow_statuses` WHERE `workflow_model_id` = @modelId;

-- delete workflow_transitions
DELETE FROM `workflow_transitions` WHERE `workflow_model_id` = @modelId;

-- delete security_functions
DELETE FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Applications';
DELETE FROM `security_functions` WHERE `controller` = 'Trainings' AND `name` = 'Applications';

-- revert staff training security_functions
UPDATE `security_functions`
SET `controller` = 'Staff', `_view` = 'TrainingNeeds.index|TrainingNeeds.view', `_edit` = 'TrainingNeeds.edit', `_add` = 'TrainingNeeds.add', `_delete` = 'TrainingNeeds.remove'
WHERE `name` = 'Needs' AND `category` = 'Staff - Training';

UPDATE `security_functions`
SET `controller` = 'Staff', `_view` = 'TrainingResults.index|TrainingResults.view'
WHERE `name` = 'Results' AND `category` = 'Staff - Training';

-- training_sessions_trainees
ALTER TABLE `training_sessions_trainees`
DROP COLUMN `status`,
CHANGE COLUMN `id` `id` CHAR(36) NOT NULL ,
DROP PRIMARY KEY,
ADD PRIMARY KEY (`id`),
DROP INDEX `id_UNIQUE`;

-- workflow_models
UPDATE `workflow_models` SET `model`='Staff.TrainingNeeds' WHERE `model`='Institution.StaffTrainingNeeds';

-- labels
DELETE FROM `labels` WHERE `module` = 'CourseCatalogue' AND `field` = 'number_of_months';
DELETE FROM `labels` WHERE `module` = 'CourseCatalogue' AND `field` = 'training_field_of_study_id';
DELETE FROM `labels` WHERE `module` = 'CourseCatalogue' AND `field` = 'training_course_type_id';
DELETE FROM `labels` WHERE `module` = 'CourseCatalogue' AND `field` = 'training_mode_of_delivery_id';
DELETE FROM `labels` WHERE `module` = 'CourseCatalogue' AND `field` = 'file_content';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3449';
