-- PHPOE-1131
--
-- 1. navigations
--

SET @idOfGeneralOverview := 0;
SET @orderOfGeneralOverview := 0;
SELECT `id`, `order` INTO @idOfGeneralOverview, @orderOfGeneralOverview FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'General' AND `title` LIKE 'Overview';

UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` >= @orderOfGeneralOverview;

INSERT INTO `navigations` (
`id` ,
`module` ,
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
NULL , 'Institution', 'InstitutionSites', 'General', 'Dashboard', 'dashboard', 'dashboard', '-1', '0', @orderOfGeneralOverview, '1', '1', '0000-00-00 00:00:00'
);

SET @idOfGeneralDashboard := 0;
SELECT `id` INTO @idOfGeneralDashboard FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'General' AND `title` LIKE 'Dashboard';
UPDATE `navigations` SET `parent` = @idOfGeneralDashboard WHERE `module` LIKE 'Institution' AND `header` LIKE 'General' AND `title` LIKE 'Overview';
UPDATE `navigations` SET `parent` = @idOfGeneralDashboard WHERE `parent` = @idOfGeneralOverview;

--
-- 2. security_functions
--

UPDATE `security_functions` SET `_view` = 'index|view|advanced|dashboard' WHERE `name` LIKE 'Institution' AND `controller` LIKE 'InstitutionSites' AND `module` LIKE 'Institutions' AND `category` LIKE 'General';

-- PHPOE-1188

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

--
-- 12. Update Security Functions for (v2.3.8)
--

UPDATE `security_functions` SET `_delete` = '_view:remove' WHERE `controller` = 'Workflows' AND `module` = 'Administration' AND `category` = 'Workflows' AND `name` = 'Workflows';
UPDATE `security_functions` SET `_delete` = '_view:remove' WHERE `controller` = 'WorkflowSteps' AND `module` = 'Administration' AND `category` = 'Workflows' AND `name` = 'WorkflowSteps';

-- PHPOE-1286

SELECT id INTO @adminBoundaryId FROM navigations WHERE title = 'Administrative Boundaries';
SELECT navigations.order INTO @sysconfigOrder FROM navigations WHERE title = 'System Configurations';

UPDATE navigations SET navigations.order = navigations.order+1 WHERE navigations.order> @sysconfigOrder;

INSERT INTO `navigations` (`module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `created_user_id`, `created`) VALUES ('Administration', NULL, 'Notices', 'System Setup', 'Notices', 'index', 'index|view|edit|add', NULL, @adminBoundaryId, 0, @sysconfigOrder+1, 1, 1, '0000-00-00 00:00:00');

-- SELECT * FROM `navigations` WHERE `module` LIKE 'Administration' ORDER BY `navigations`.`order` ASC;
-- SELECT * FROM `navigations` WHERE navigations.order >53;



CREATE TABLE `notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;


SELECT MAX(security_functions.order) INTO @lastOrder from security_functions WHERE visible = 1;
INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('Notices', 'Notices', 'Administration', 'Notices', -1, 'index|view', '_view:edit', '_view:add', '_view:delete', NULL, @lastOrder + 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00');


-- remove dashboard_notice
CREATE TABLE IF NOT EXISTS z_1286_config_items LIKE config_items;
INSERT INTO z_1286_config_items SELECT * FROM config_items WHERE name = 'dashboard_notice' AND NOT EXISTS (SELECT * FROM z_1286_config_items);

DELETE from config_items WHERE name = 'dashboard_notice';

ALTER TABLE `notices` CHANGE `message` `message` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
