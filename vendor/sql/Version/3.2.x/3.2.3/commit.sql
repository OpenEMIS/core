ALTER TABLE  `db_patches` ADD  `created` DATETIME NOT NULL ;

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1381', NOW());

-- institution_site_survey_answer
ALTER TABLE `institution_site_survey_answers` 
ADD INDEX `survey_question_id` (`survey_question_id`);

ALTER TABLE `institution_site_survey_answers`
ADD INDEX `institution_site_survey_id` (`institution_site_survey_id`);

-- PHPOE-1414
INSERT INTO `db_patches` VALUES ('PHPOE-1414', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES
(uuid(), 'StudentFees', 'openemis_no', 'Student -> Fees', 'OpenEMIS ID', '1', '0', NOW()),
(uuid(), 'InstitutionFees', 'total', 'Institution -> Finance -> Fees', 'Total Fee', '1', '0', NOW());

UPDATE `labels` set `field`='amount_paid', `field_name`='Amount Paid' WHERE `module`='StudentFees' AND `field`='paid';
UPDATE `labels` set `field`='outstanding_fee', `field_name`='Outstanding Fee' WHERE `module`='StudentFees' AND `field`='outstanding';

ALTER TABLE `student_fees` 	CHANGE `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users', 
							CHANGE `institution_site_fee_id` `institution_fee_id` INT(11) NOT NULL;

CREATE TABLE IF NOT EXISTS `z_1414_institution_site_fee_types` LIKE `institution_site_fee_types`;
INSERT INTO `z_1414_institution_site_fee_types` SELECT * FROM `institution_site_fee_types`;
ALTER TABLE `institution_site_fee_types` CHANGE `institution_site_fee_id` `institution_fee_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_fee_types` RENAME `institution_fee_types`;

CREATE TABLE IF NOT EXISTS `z_1414_institution_site_fees` LIKE `institution_site_fees`;
INSERT INTO `z_1414_institution_site_fees` SELECT * FROM `institution_site_fees`;
ALTER TABLE `institution_site_fees` CHANGE `institution_site_id` `institution_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_fees` CHANGE `total` `total` DECIMAL(20,2) NULL DEFAULT NULL;
ALTER TABLE `institution_site_fees` RENAME `institution_fees`;

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1430', NOW());

-- student_custom_field_values
ALTER TABLE `student_custom_field_values` 
ADD INDEX `student_custom_field_id` (`student_custom_field_id`);

ALTER TABLE `student_custom_field_values` 
ADD INDEX `security_user_id` (`security_user_id`);

-- staff_custom_field_values
ALTER TABLE `staff_custom_field_values` 
ADD INDEX `staff_custom_field_id` (`staff_custom_field_id`);

ALTER TABLE `staff_custom_field_values` 
ADD INDEX `security_user_id` (`security_user_id`);

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2028', NOW());

--
-- Table structure for table `api_authorizations`
--
DROP TABLE IF EXISTS `api_authorizations`;
CREATE TABLE IF NOT EXISTS `api_authorizations` ( 
  `id` char(36) NOT NULL,
  `name` varchar(128) NOT NULL,
  `security_token` char(40) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime NOT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for table `api_authorizations`
--
ALTER TABLE `api_authorizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY (`security_token`);

INSERT INTO `api_authorizations` (`id`, `name`, `security_token`) values 
('00e588d8-6293-42ef-a0fe-395a63adf979', 'External Application Tester', 'acd87adcas9d8cad');

INSERT INTO `db_patches` VALUES ('PHPOE-2103', NOW());

-- staff_leave_attachments
DROP TABLE IF EXISTS `staff_leave_attachments`;

-- staff_leaves
ALTER TABLE `staff_leaves` ADD `file_name` VARCHAR(250) NULL AFTER `number_of_days`, ADD `file_content` LONGBLOB NULL AFTER `file_name`;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'Leaves', 'file_content', 'Staff -> Career -> Leave','Attachment', 1, 1, NOW());

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Leaves.download' WHERE `id` = 3016;

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2078', NOW());

-- staff_leaves
DROP TABLE IF EXISTS `staff_leaves`;
CREATE TABLE IF NOT EXISTS `staff_leaves` (
  `id` int(11) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `comments` text,
  `security_user_id` int(11) NOT NULL,
  `staff_leave_type_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `number_of_days` int(3) NOT NULL,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `staff_leaves`
  ADD PRIMARY KEY (`id`), ADD KEY `security_user_id` (`security_user_id`), ADD KEY `staff_leave_type_id` (`staff_leave_type_id`), ADD KEY `status_id` (`status_id`);


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
  ADD PRIMARY KEY (`id`), ADD KEY `workflow_model_id` (`workflow_model_id`);


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
  `event_key` varchar(200) DEFAULT NULL,
  `comment_required` int(1) NOT NULL DEFAULT '0',
  `workflow_step_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `workflow_actions`
  ADD PRIMARY KEY (`id`), ADD KEY `next_workflow_step_id` (`next_workflow_step_id`), ADD KEY `workflow_step_id` (`workflow_step_id`);


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
  ADD PRIMARY KEY (`id`), ADD KEY `model_reference` (`model_reference`), ADD KEY `workflow_model_id` (`workflow_model_id`), ADD KEY `workflow_step_id` (`workflow_step_id`);


ALTER TABLE `workflow_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- workflow_steps
DROP TABLE IF EXISTS `workflow_steps`;
CREATE TABLE IF NOT EXISTS `workflow_steps` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `stage` int(1) DEFAULT NULL COMMENT '0 -> Open, 1 -> Closed',
  `is_editable` int(1) NOT NULL DEFAULT '0',
  `is_removable` int(1) NOT NULL DEFAULT '0',
  `workflow_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `workflow_steps`
  ADD PRIMARY KEY (`id`), ADD KEY `workflow_id` (`workflow_id`);


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
  `comment` text NOT NULL,
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
  ADD PRIMARY KEY (`id`), ADD KEY `prev_workflow_step_id` (`prev_workflow_step_id`), ADD KEY `workflow_step_id` (`workflow_step_id`), ADD KEY `workflow_action_id` (`workflow_action_id`), ADD KEY `workflow_record_id` (`workflow_record_id`);


ALTER TABLE `workflow_transitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'WorkflowSteps', 'is_editable', 'Workflow -> Steps', 'Editable', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'WorkflowSteps', 'is_removable', 'Workflow -> Steps', 'Removable', 1, 1, NOW());

-- field_options
SET @parentId := 0;
SELECT `id` INTO @parentId FROM `field_options` WHERE `code` = 'LeaveStatuses';
DELETE FROM `field_option_values` WHERE `field_option_id` = @parentId;
DELETE FROM `field_options` WHERE `id` = @parentId;

INSERT INTO `db_patches` VALUES ('PHPOE-2124', NOW());

-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Assessments.view', `_edit` = 'Assessments.edit' WHERE `id` = 1015;

-- assessment_item_results
ALTER TABLE `assessment_item_results` CHANGE `id` `id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `assessment_item_results` CHANGE `marks` `marks` INT(5) NULL DEFAULT NULL;
ALTER TABLE `assessment_item_results` CHANGE `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `assessment_item_results` CHANGE `institution_site_id` `institution_id` INT(11) NOT NULL;

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2144', NOW());

-- institution_custom_field_values
ALTER TABLE `institution_custom_field_values` 
ADD INDEX `institution_custom_field_id` (`institution_custom_field_id`);

ALTER TABLE `institution_custom_field_values` 
ADD INDEX `institution_site_id` (`institution_site_id`);

DROP TABLE IF EXISTS `z1407_assessment_item_results`;
DROP TABLE IF EXISTS `z1407_assessment_results`;
DROP TABLE IF EXISTS `z1407_institution_site_class_staff`;
DROP TABLE IF EXISTS `z1407_institution_site_class_students`;
DROP TABLE IF EXISTS `z1407_institution_site_quality_rubrics`;
DROP TABLE IF EXISTS `z1407_institution_site_quality_visits`;
DROP TABLE IF EXISTS `z1407_institution_site_sections`;
DROP TABLE IF EXISTS `z1407_institution_site_section_staff`;
DROP TABLE IF EXISTS `z1407_institution_site_section_students`;
DROP TABLE IF EXISTS `z1407_institution_site_staff`;
DROP TABLE IF EXISTS `z1407_institution_site_staff_absences`;
DROP TABLE IF EXISTS `z1407_institution_site_students`;
DROP TABLE IF EXISTS `z1407_institution_site_student_absences`;
DROP TABLE IF EXISTS `z1407_staff_activities`;
DROP TABLE IF EXISTS `z1407_staff_attachments`;
DROP TABLE IF EXISTS `z1407_staff_attendances`;
DROP TABLE IF EXISTS `z1407_staff_bank_accounts`;
DROP TABLE IF EXISTS `z1407_staff_behaviours`;
DROP TABLE IF EXISTS `z1407_staff_custom_values`;
DROP TABLE IF EXISTS `z1407_staff_custom_value_history`;
DROP TABLE IF EXISTS `z1407_staff_details_custom_values`;
DROP TABLE IF EXISTS `z1407_staff_employments`;
DROP TABLE IF EXISTS `z1407_staff_extracurriculars`;
DROP TABLE IF EXISTS `z1407_staff_healths`;
DROP TABLE IF EXISTS `z1407_staff_health_allergies`;
DROP TABLE IF EXISTS `z1407_staff_health_consultations`;
DROP TABLE IF EXISTS `z1407_staff_health_families`;
DROP TABLE IF EXISTS `z1407_staff_health_histories`;
DROP TABLE IF EXISTS `z1407_staff_health_immunizations`;
DROP TABLE IF EXISTS `z1407_staff_health_medications`;
DROP TABLE IF EXISTS `z1407_staff_health_tests`;

UPDATE `config_items` SET `value` = '3.2.3' WHERE `code` = 'db_version';

