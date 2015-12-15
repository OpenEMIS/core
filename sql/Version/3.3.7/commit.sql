-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1978', NOW());

-- Backup Admin - training tables
CREATE TABLE `z_1978_staff_training_needs` LIKE `staff_training_needs`;
INSERT INTO `z_1978_staff_training_needs` SELECT * FROM `staff_training_needs` WHERE 1;
DROP TABLE IF EXISTS `staff_training_needs`;

-- New table - staff_training_needs
DROP TABLE IF EXISTS `staff_training_needs`;
CREATE TABLE `staff_training_needs` (
  `id` int(11) NOT NULL,
  `comments` text DEFAULT NULL,
  `course_code` varchar(60) NOT NULL,
  `course_name` varchar(250) NOT NULL,
  `course_description` text DEFAULT NULL,
  `course_id` int(11) NOT NULL COMMENT 'links to training_courses.id',
  `training_need_category_id` int(11) NOT NULL,
  `training_requirement_id` int(11) NOT NULL,
  `training_priority_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `staff_training_needs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `training_need_category_id` (`training_need_category_id`),
  ADD KEY `training_requirement_id` (`training_requirement_id`),
  ADD KEY `training_priority_id` (`training_priority_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `status_id` (`status_id`);

ALTER TABLE `staff_training_needs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `filter`, `created_user_id`, `created`) VALUES
('Staff > Training > Needs', 'Staff.TrainingNeeds', NULL, 1, NOW());

-- Workflow for Training > Needs
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Staff.TrainingNeeds';
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('TRN-4001', 'Training Needs', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'TRN-4001' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_steps` (`name`, `stage`, `is_editable`, `is_removable`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 0, 1, 1, @workflowId, 1, NOW()),
('Pending For Approval', 1, 0, 0, @workflowId, 1, NOW()),
('Closed', 2, 0, 0, @workflowId, 1, NOW()),
('Approved', NULL, 0, 0, @workflowId, 1, NOW());

SET @openStepId := 0;
SET @approvalStepId := 0;
SET @closedStepId := 0;
SET @approvedStepId := 0;
SELECT `id` INTO @openStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Open' AND `stage` = 0;
SELECT `id` INTO @approvalStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Pending For Approval' AND `stage` = 1;
SELECT `id` INTO @closedStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Closed' AND `stage` = 2;
SELECT `id` INTO @approvedStepId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `name` = 'Approved';

INSERT INTO `workflow_actions` (`name`, `action`, `visible`, `next_workflow_step_id`, `event_key`, `comment_required`, `workflow_step_id`, `created_user_id`, `created`) VALUES
('Submit For Approval', 0, 1, @approvalStepId, '', 0, @openStepId, 1, NOW()),
('Cancel', 1, 1, @closedStepId, '', 1, @openStepId, 1, NOW()),
('Approve', 0, 1, @approvedStepId, '', 0, @approvalStepId, 1, NOW()),
('Reject', 1, 1, @openStepId, '', 1, @approvalStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @closedStepId, 1, NOW()),
('Reopen', NULL, 1, @openStepId, '', 1, @closedStepId, 1, NOW()),
('Approve', 0, 0, 0, '', 0, @approvedStepId, 1, NOW()),
('Reject', 1, 0, 0, '', 0, @approvedStepId, 1, NOW()),
('Inactive', NULL, 1, @closedStepId, '', 1, @approvedStepId, 1, NOW());

INSERT INTO `workflow_statuses` (`code`, `name`, `is_editable`, `is_removable`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('PENDING', 'Pending', 0, 0, @modelId, 1, NOW()),
('APPROVED', 'Approved', 0, 0, @modelId, 1, NOW());

SET @pendingId := 0;
SET @approvedId := 0;
SELECT `id` INTO @pendingId FROM `workflow_statuses` WHERE `code` = 'PENDING' AND `workflow_model_id` = @modelId;
SELECT `id` INTO @approvedId FROM `workflow_statuses` WHERE `code` = 'APPROVED' AND `workflow_model_id` = @modelId;
INSERT INTO `workflow_statuses_steps` (`id`, `workflow_status_id`, `workflow_step_id`) VALUES
(uuid(), @pendingId, @approvalStepId),
(uuid(), @approvedId, @approvedStepId);

INSERT INTO `db_patches` VALUES ('PHPOE-2069', NOW());

ALTER TABLE `area_administratives` 
CHANGE COLUMN `parent_id` `parent_id` INT(11) NULL COMMENT '',
ADD COLUMN `is_main_country` INT(1) NOT NULL DEFAULT 0 COMMENT '' AFTER `name`;

UPDATE `area_administratives`
INNER JOIN (
	SELECT `id`
    FROM `area_administratives`
    WHERE `area_administrative_level_id` = 1
    ORDER BY `id`
    LIMIT 1
) `tmp` ON `area_administratives`.`id` = `tmp`.`id`
SET `is_main_country` = 1;

ALTER TABLE `areas` 
CHANGE COLUMN `parent_id` `parent_id` INT(11) NULL COMMENT '';
-- PHPOE-2086
INSERT INTO `db_patches` VALUES ('PHPOE-2086', NOW());

CREATE TABLE `z2086_import_mapping` LIKE `import_mapping`;
INSERT INTO `z2086_import_mapping` SELECT * FROM `import_mapping`;

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
values
('InstitutionSiteSurveys', 'survey_form_id', '', '1', '2', 'Survey', 'SurveyForms', 'id')
;

CREATE TABLE `z2086_survey_forms` LIKE `survey_forms`;
INSERT INTO `z2086_survey_forms` SELECT * FROM `survey_forms`;

ALTER TABLE `survey_forms`  ADD `code` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL  AFTER `id`;
UPDATE `survey_forms` set `code`=(LEFT(UUID(), 8)) where 1;

CREATE TABLE `z2086_survey_questions` LIKE `survey_questions`;
INSERT INTO `z2086_survey_questions` SELECT * FROM `survey_questions`;

ALTER TABLE `survey_questions`  ADD `code` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL  AFTER `id`;
UPDATE `survey_questions` set `code`=(LEFT(UUID(), 8)) where 1;

CREATE TABLE `z_2086_security_functions` LIKE `security_functions`;

INSERT INTO `z_2086_security_functions` 
SELECT * FROM `security_functions` WHERE `id` IN (1024, 1025);

UPDATE `security_functions` SET `name`='Import', `_view`=NULL, `_edit`=NULL, `_delete`=NULL, `_execute` = 'ImportInstitutionSurveys.add|ImportInstitutionSurveys.template|ImportInstitutionSurveys.results' WHERE `id`=1024;
UPDATE `security_functions` SET `name`='Surveys', `_view`='Surveys.index|Surveys.view', `_edit`='Surveys.edit', `_delete`='Surveys.remove', `_execute` = 'Survey.excel' WHERE `id`=1025;

-- security_role_functions
CREATE TABLE `z_2086_security_role_functions` LIKE `security_role_functions`;

INSERT INTO `z_2086_security_role_functions`
SELECT * FROM `security_role_functions` WHERE `security_function_id` IN (1024, 1025);

UPDATE `security_role_functions` SET `security_function_id` = 0 WHERE `security_function_id` IN (1024, 1025);

UPDATE config_items SET value = '3.3.7' WHERE code = 'db_version';
