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
(1, 'Staff Leave', 'StaffLeave', 'FieldOption.StaffLeaveTypes', 1, '0000-00-00 00:00:00');

-- workflow_records
DROP TABLE IF EXISTS `workflow_records`;
CREATE TABLE IF NOT EXISTS `workflow_records` (
  `id` int(11) NOT NULL,
  `model_reference` int(11) NOT NULL,
  `workflow_model_id` int(11) NOT NULL,
  `workflow_step_id` int(11) NOT NULL,
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
  `id` int(11) NOT NULL,
  `workflow_step_id` int(11) NOT NULL,
  `security_role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `workflow_steps_roles`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `workflow_steps_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1391';
