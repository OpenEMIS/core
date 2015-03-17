--
-- 1. New table - wf_workflow_models
--

DROP TABLE IF EXISTS `wf_workflow_models`;
CREATE TABLE IF NOT EXISTS `wf_workflow_models` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `model` varchar(200) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflow_models`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflow_models`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

INSERT INTO `wf_workflow_models` (`id`, `name`, `model`, `created_user_id`, `created`) VALUES (NULL, 'Staff Leave', 'StaffLeave', '1', '0000-00-00 00:00:00');

--
-- 2. New table - wf_workflows
--

DROP TABLE IF EXISTS `wf_workflows`;
CREATE TABLE IF NOT EXISTS `wf_workflows` (
`id` int(11) NOT NULL,
  `code` varchar(60) NOT NULL,
  `name` varchar(100) NOT NULL,
  `workflow_model_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflows`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflows`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 3. New table - wf_workflow_steps
--

DROP TABLE IF EXISTS `wf_workflow_steps`;
CREATE TABLE IF NOT EXISTS `wf_workflow_steps` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `stage` int(1) DEFAULT NULL COMMENT '0 -> Open, 1 -> Closed',
  `workflow_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflow_steps`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflow_steps`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 4. New table - wf_workflow_actions
--

DROP TABLE IF EXISTS `wf_workflow_actions`;
CREATE TABLE IF NOT EXISTS `wf_workflow_actions` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `next_workflow_step_id` int(11) NOT NULL,
  `workflow_step_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflow_actions`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflow_actions`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 5. New table - wf_workflow_step_roles
--

DROP TABLE IF EXISTS `wf_workflow_step_roles`;
CREATE TABLE IF NOT EXISTS `wf_workflow_step_roles` (
`id` int(11) NOT NULL,
  `workflow_step_id` int(11) NOT NULL,
  `security_role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflow_step_roles`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflow_step_roles`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 6. New table - wf_workflow_records
--

DROP TABLE IF EXISTS `wf_workflow_records`;
CREATE TABLE IF NOT EXISTS `wf_workflow_records` (
`id` int(11) NOT NULL,
  `model_reference` int(11) NOT NULL,
  `workflow_model_id` int(11) NOT NULL,
  `workflow_step_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflow_records`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflow_records`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 7. New table - wf_workflow_comments
--

DROP TABLE IF EXISTS `wf_workflow_comments`;
CREATE TABLE IF NOT EXISTS `wf_workflow_comments` (
`id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `workflow_record_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflow_comments`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflow_comments`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 8. New table - wf_workflow_transitions
--

DROP TABLE IF EXISTS `wf_workflow_transitions`;
CREATE TABLE IF NOT EXISTS `wf_workflow_transitions` (
`id` int(11) NOT NULL,
  `prev_workflow_step_id` int(11) NOT NULL,
  `workflow_step_id` int(11) NOT NULL,
  `workflow_action_id` int(11) NOT NULL,
  `workflow_record_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflow_transitions`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflow_transitions`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 9. Drop unused table - wf_workflow_logs
--

DROP TABLE IF EXISTS `wf_workflow_logs`;

--
-- 10. Navigations
--

UPDATE `navigations` SET `action` = 'StaffLeave', `pattern` = 'StaffLeave' WHERE `controller` = 'Staff' AND `header` = 'Details' AND `title` = 'Leave';

--
-- 11. Security Functions
--

UPDATE `security_functions` SET `_view` = 'StaffLeave|StaffLeave.index', `_edit` = '_view:StaffLeave.edit' , `_add` = '_view:StaffLeave.add', `_delete` = '_view:StaffLeave.delete' WHERE `controller` = 'Staff' AND `module` = 'Staff' AND `category` = 'Details' AND `name` = 'Leave';
