--
-- 1. New table - wf_workflows
--

DROP TABLE IF EXISTS `wf_workflows`;
CREATE TABLE IF NOT EXISTS `wf_workflows` (
`id` int(11) NOT NULL,
  `code` varchar(60) NOT NULL,
  `name` varchar(100) NOT NULL,
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
-- 2. New table - wf_workflow_steps
--

DROP TABLE IF EXISTS `wf_workflow_steps`;
CREATE TABLE IF NOT EXISTS `wf_workflow_steps` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `wf_workflow_id` int(11) NOT NULL,
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
-- 3. New table - wf_workflow_step_roles
--

DROP TABLE IF EXISTS `wf_workflow_step_roles`;
CREATE TABLE IF NOT EXISTS `wf_workflow_step_roles` (
`id` int(11) NOT NULL,
  `wf_workflow_step_id` int(11) NOT NULL,
  `security_role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflow_step_roles`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflow_step_roles`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 4. New table - wf_workflow_actions
--

DROP TABLE IF EXISTS `wf_workflow_actions`;
CREATE TABLE IF NOT EXISTS `wf_workflow_actions` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `next_wf_workflow_step_id` int(11) NOT NULL,
  `wf_workflow_step_id` int(11) NOT NULL,
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
-- 5. New table - wf_workflow_logs
--

DROP TABLE IF EXISTS `wf_workflow_logs`;
CREATE TABLE IF NOT EXISTS `wf_workflow_logs` (
`id` int(11) NOT NULL,
  `reference_table` varchar(200) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `comments` text NOT NULL,
  `wf_workflow_step_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflow_logs`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflow_logs`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 6. navigations
--

SET @orderOfQualityStatus := 0;
SELECT `order` INTO @orderOfQualityStatus FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Quality' AND `title` LIKE 'Status';

UPDATE `navigations` SET `order` = `order` + 3 WHERE `order` > @orderOfQualityStatus;

SET @orderOfQualityStatus := @orderOfQualityStatus + 1;

INSERT INTO `navigations` (
`id` ,
`module` ,
`plugin` ,
`controller` ,
`header` ,
`title` ,
`action` ,
`pattern` ,
`parent` ,
`is_wizard` ,
`order` ,
`visible` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Administration', 'Workflows', 'Workflows', 'Workflows', 'Workflows', 'index', 'index|view|edit|add|delete', '33', '0', @orderOfQualityStatus, '1', '1', '0000-00-00 00:00:00'
);

SET @orderOfQualityStatus := @orderOfQualityStatus + 1;

INSERT INTO `navigations` (
`id` ,
`module` ,
`plugin` ,
`controller` ,
`header` ,
`title` ,
`action` ,
`pattern` ,
`parent` ,
`is_wizard` ,
`order` ,
`visible` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Administration', 'Workflows', 'WorkflowSteps', 'Workflows', 'Steps', 'index', 'index|view|edit|add|delete', '33', '0', @orderOfQualityStatus, '1', '1', '0000-00-00 00:00:00'
);

SET @orderOfQualityStatus := @orderOfQualityStatus + 1;

INSERT INTO `navigations` (
`id` ,
`module` ,
`plugin` ,
`controller` ,
`header` ,
`title` ,
`action` ,
`pattern` ,
`parent` ,
`is_wizard` ,
`order` ,
`visible` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Administration', 'Workflows', 'WorkflowLogs', 'Workflows', 'Logs', 'index', 'index|view|edit|add|delete', '33', '0', @orderOfQualityStatus, '1', '1', '0000-00-00 00:00:00'
);

--
-- 7. security_functions
--

SET @orderOfQualityStatus := 0;
SELECT `order` INTO @orderOfQualityStatus FROM `security_functions` WHERE `controller` LIKE 'Quality' AND `category` LIKE 'Quality' AND `name` LIKE 'Status';

UPDATE `security_functions` SET `order` = `order` + 3 WHERE `order` > @orderOfQualityStatus;

SET @orderOfQualityStatus := @orderOfQualityStatus + 1;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_view` ,
`_edit` ,
`_add` ,
`_delete` ,
`_execute` ,
`order` ,
`visible` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Workflows', 'Workflows', 'Administration', 'Workflows', '-1', 'index|view', '_view:edit', '_view:add', '_view:delete', NULL , @orderOfQualityStatus, '1', '1', '0000-00-00 00:00:00'
);

SET @orderOfQualityStatus := @orderOfQualityStatus + 1;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_view` ,
`_edit` ,
`_add` ,
`_delete` ,
`_execute` ,
`order` ,
`visible` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'WorkflowSteps', 'WorkflowSteps', 'Administration', 'Workflows', '-1', 'index|view', '_view:edit', '_view:add', '_view:delete', NULL , @orderOfQualityStatus, '1', '1', '0000-00-00 00:00:00'
);

SET @orderOfQualityStatus := @orderOfQualityStatus + 1;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_view` ,
`_edit` ,
`_add` ,
`_delete` ,
`_execute` ,
`order` ,
`visible` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'WorkflowLogs', 'WorkflowLogs', 'Administration', 'Workflows', '-1', 'index|view', '_view:edit', '_view:add', '_view:delete', NULL , @orderOfQualityStatus, '1', '1', '0000-00-00 00:00:00'
);
