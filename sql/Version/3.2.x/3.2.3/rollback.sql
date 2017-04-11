
-- institution_site_survey_answer
ALTER TABLE `institution_site_survey_answers` 
DROP INDEX `institution_site_survey_id`;

ALTER TABLE `institution_site_survey_answers` 
DROP INDEX `survey_question_id` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1381';

-- PHPOE-1414
DELETE FROM `labels` WHERE `labels`.`module`='StudentFees' AND `labels`.`field`='openemis_no';
DELETE FROM `labels` WHERE `labels`.`module`='InstitutionFees' AND `labels`.`field`='total';

UPDATE `labels` set `field`='paid', `field_name`='Paid' WHERE `module`='StudentFees' AND `field`='amount_paid';
UPDATE `labels` set `field`='outstanding', `field_name`='Outstanding' WHERE `module`='StudentFees' AND `field`='outstanding_fee';

ALTER TABLE `student_fees` 	CHANGE `student_id` `security_user_id` INT(11) NOT NULL, 
							CHANGE `institution_fee_id` `institution_site_fee_id` INT(11) NOT NULL;

DROP TABLE IF EXISTS `institution_fee_types`;
ALTER TABLE `z_1414_institution_site_fee_types` RENAME `institution_site_fee_types`;

DROP TABLE IF EXISTS `institution_fees`;
ALTER TABLE `z_1414_institution_site_fees` RENAME `institution_site_fees`;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1414';

-- student_custom_field_values
ALTER TABLE `student_custom_field_values` 
DROP INDEX `security_user_id`;

ALTER TABLE `student_custom_field_values` 
DROP INDEX `student_custom_field_id` ;

-- staff_custom_field_values
ALTER TABLE `staff_custom_field_values` 
DROP INDEX `security_user_id`;

ALTER TABLE `staff_custom_field_values` 
DROP INDEX `staff_custom_field_id` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1430';

DROP TABLE IF EXISTS `api_authorizations`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2028';

-- staff_leaves
DROP TABLE IF EXISTS `staff_leaves`;
CREATE TABLE IF NOT EXISTS `staff_leaves` (
  `id` int(11) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `comments` text,
  `security_user_id` int(11) NOT NULL,
  `staff_leave_type_id` int(11) NOT NULL,
  `leave_status_id` int(11) NOT NULL,
  `number_of_days` int(3) NOT NULL,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `staff_leaves`
  ADD PRIMARY KEY (`id`), ADD KEY `staff_leave_type_id` (`staff_leave_type_id`), ADD KEY `leave_status_id` (`leave_status_id`), ADD KEY `security_user_id` (`security_user_id`);


ALTER TABLE `staff_leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- workflows
DROP TABLE IF EXISTS `workflows`;
CREATE TABLE IF NOT EXISTS `workflows` (
  `id` int(11) NOT NULL,
  `code` varchar(60) NOT NULL,
  `name` varchar(100) NOT NULL,
  `workflow_model_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `workflows`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `workflows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- workflows_filters
DROP TABLE IF EXISTS `workflows_filters`;
CREATE TABLE IF NOT EXISTS `workflows_filters` (
  `id` char(36) NOT NULL,
  `workflow_id` int(11) NOT NULL,
  `filter_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `workflows_filters`
  ADD PRIMARY KEY (`id`);

-- workflow_actions
DROP TABLE IF EXISTS `workflow_actions`;
CREATE TABLE IF NOT EXISTS `workflow_actions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `action` int(1) DEFAULT NULL COMMENT '0 -> Approve, 1 -> Reject',
  `visible` int(1) NOT NULL DEFAULT '1',
  `next_workflow_step_id` int(11) NOT NULL,
  `comment_required` int(1) NOT NULL DEFAULT '0',
  `workflow_step_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `workflow_actions`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `workflow_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- workflow_comments
DROP TABLE IF EXISTS `workflow_comments`;
CREATE TABLE IF NOT EXISTS `workflow_comments` (
  `id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `workflow_record_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `workflow_comments`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `workflow_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- workflow_models
DROP TABLE IF EXISTS `workflow_models`;
CREATE TABLE IF NOT EXISTS `workflow_models` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `model` varchar(200) NOT NULL,
  `filter` varchar(200) DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `workflow_models`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `workflow_models`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

INSERT INTO `workflow_models` (`name`, `model`, `filter`, `created_user_id`, `created`) VALUES
('Staff > Career > Leave', 'Staff.Leaves', 'FieldOption.StaffLeaveTypes', 1, '0000-00-00 00:00:00');

-- workflow_records
DROP TABLE IF EXISTS `workflow_records`;
CREATE TABLE IF NOT EXISTS `workflow_records` (
  `id` int(11) NOT NULL,
  `model_reference` int(11) NOT NULL,
  `workflow_model_id` int(11) NOT NULL,
  `workflow_step_id` int(11) NOT NULL COMMENT 'The latest Workflow Step',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `workflow_records`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `workflow_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- workflow_steps
DROP TABLE IF EXISTS `workflow_steps`;
CREATE TABLE IF NOT EXISTS `workflow_steps` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `stage` int(1) DEFAULT NULL COMMENT '0 -> Open, 1 -> Closed',
  `workflow_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `workflow_steps`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `workflow_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- workflow_steps_roles
DROP TABLE IF EXISTS `workflow_steps_roles`;
CREATE TABLE IF NOT EXISTS `workflow_steps_roles` (
  `id` char(36) NOT NULL,
  `workflow_step_id` int(11) NOT NULL,
  `security_role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `workflow_steps_roles`
  ADD PRIMARY KEY (`id`);

-- workflow_transitions
DROP TABLE IF EXISTS `workflow_transitions`;
CREATE TABLE IF NOT EXISTS `workflow_transitions` (
  `id` int(11) NOT NULL,
  `comment` text,
  `prev_workflow_step_id` int(11) NOT NULL,
  `workflow_step_id` int(11) NOT NULL,
  `workflow_action_id` int(11) NOT NULL,
  `workflow_record_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `workflow_transitions`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `workflow_transitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- labels
DELETE FROM `labels` WHERE `module` = 'WorkflowSteps' AND `field` = 'is_editable';
DELETE FROM `labels` WHERE `module` = 'WorkflowSteps' AND `field` = 'is_removable';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2078';

-- staff_leave_attachments
DROP TABLE IF EXISTS `staff_leave_attachments`;
CREATE TABLE IF NOT EXISTS `staff_leave_attachments` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` varchar(250) NOT NULL,
  `file_name` varchar(250) NOT NULL,
  `file_content` longblob NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `staff_leave_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `staff_leave_attachments`
  ADD PRIMARY KEY (`id`), ADD KEY `staff_leave_id` (`staff_leave_id`);


ALTER TABLE `staff_leave_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- staff_leaves
ALTER TABLE `staff_leaves`
  DROP `file_name`,
  DROP `file_content`;

-- labels
DELETE FROM `labels` WHERE `module` = 'Leaves' AND `field` = 'file_content';

-- security_functions
UPDATE `security_functions` SET `_execute` = NULL WHERE `id` = 3016;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2103';

-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Results.index', `_edit` = 'Results.indexEdit' WHERE `id` = 1015;

-- assessment_item_results
ALTER TABLE `assessment_item_results` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `assessment_item_results` CHANGE `marks` `marks` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `assessment_item_results` CHANGE `student_id` `security_user_id` INT(11) NOT NULL;
ALTER TABLE `assessment_item_results` CHANGE `institution_id` `institution_site_id` INT(11) NOT NULL;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2124';


-- institution_custom_field_values
ALTER TABLE `institution_custom_field_values` 
DROP INDEX `institution_site_id`;

ALTER TABLE `institution_custom_field_values` 
DROP INDEX `institution_custom_field_id` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2144';

UPDATE `config_items` SET `value` = '3.2.2' WHERE `code` = 'db_version';

