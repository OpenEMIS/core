-- PHPOE-1391
-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1391');

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

TRUNCATE TABLE `workflow_models`;
INSERT INTO `workflow_models` (`id`, `name`, `model`, `filter`, `created_user_id`, `created`) VALUES
(1, 'Staff Leave', 'Staff.Leaves', 'FieldOption.StaffLeaveTypes', 1, '0000-00-00 00:00:00');

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
  ADD PRIMARY KEY (`id`);


ALTER TABLE `workflow_transitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


-- PHPOE-1892
INSERT INTO `db_patches` VALUES ('PHPOE-1892');

INSERT INTO `student_statuses` (`code`, `name`)
VALUES ('PENDING_ADMISSION', 'Pending Admission');

INSERT INTO `student_statuses` (`code`, `name`)
VALUES ('REJECTED', 'Rejected');

ALTER TABLE `institution_student_transfers` 
CHANGE COLUMN `security_user_id` `student_id` INT(11) NOT NULL COMMENT '' ,
ADD COLUMN `type` INT(1) NOT NULL DEFAULT 2 COMMENT '1 -> Admission, 2 -> Transfer' AFTER `comment`, RENAME TO  `institution_student_admission` ;

UPDATE `security_functions` SET `controller`='Institutions', `_view`='TransferApprovals.view', `_execute`='TransferApprovals.edit|TransferApprovals.view' WHERE `name`='Transfer Approval';
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_execute`, `order`, `visible`, `created_user_id`, `created`) 
VALUES (1028, 'Student Admission', 'Institutions', 'Institutions', 'Students', 1000, 'StudentAdmission.index|StudentAdmission.view', 'StudentAdmission.edit|StudentAdmission.view', 1024, 1, 1, NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StudentAdmission', 'created', 'Institutions -> Student Admission','Date of Application', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'TransferApprovals', 'created', 'Institutions -> Transfer Approvals','Date of Application', 1, 1, NOW());

UPDATE `config_items` SET `value` = '3.1.5' WHERE `code` = 'db_version';
