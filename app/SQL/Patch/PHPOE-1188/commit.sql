--
-- 1. New table - wf_workflow_models
--

DROP TABLE IF EXISTS `wf_workflow_models`;
CREATE TABLE IF NOT EXISTS `wf_workflow_models` (
`id` int(11) NOT NULL,
  `model` varchar(200) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflow_models`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflow_models`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 2. New table - wf_workflow_comments
--

DROP TABLE IF EXISTS `wf_workflow_comments`;
CREATE TABLE IF NOT EXISTS `wf_workflow_comments` (
`id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `workflow_log_id` int(11) NOT NULL,
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
-- 3. Alter table wf_workflows
--

ALTER TABLE `wf_workflows` ADD `workflow_model_id` INT(11) NOT NULL AFTER `name`;

--
-- 4. Alter table wf_workflow_steps
--

ALTER TABLE `wf_workflow_steps` CHANGE `wf_workflow_id` `workflow_id` INT(11) NOT NULL;
ALTER TABLE `wf_workflow_steps` ADD `editable` INT(1) NOT NULL DEFAULT '1' AFTER `name`;

--
-- 5. Alter table wf_workflow_actions
--

ALTER TABLE `wf_workflow_actions` CHANGE `wf_workflow_step_id` `workflow_step_id` INT(11) NOT NULL;
ALTER TABLE `wf_workflow_actions` CHANGE `next_wf_workflow_step_id` `next_workflow_step_id` INT(11) NOT NULL;

--
-- 6. Alter table wf_workflow_step_roles
--

ALTER TABLE `wf_workflow_step_roles` CHANGE `wf_workflow_step_id` `workflow_step_id` INT(11) NOT NULL;

--
-- 7. Alter table wf_workflow_logs
--

ALTER TABLE `wf_workflow_logs` CHANGE `reference_table` `model` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `wf_workflow_logs` CHANGE `reference_id` `model_reference` INT(11) NOT NULL;
ALTER TABLE `wf_workflow_logs` CHANGE `wf_workflow_step_id` `workflow_step_id` INT(11) NOT NULL;
ALTER TABLE `wf_workflow_logs` DROP `comments`;
ALTER TABLE `wf_workflow_logs` ADD `prev_workflow_step_id` INT(11) NOT NULL AFTER `model_reference`;

--
-- 8. Navigations
--

UPDATE `navigations` SET `action` = 'StaffLeave', `pattern` = 'StaffLeave' WHERE `controller` = 'Staff' AND `header` = 'Details' AND `title` = 'Leave';

--
-- 9. Security Functions
--

UPDATE `security_functions` SET `_view` = 'StaffLeave|StaffLeave.index', `_edit` = '_view:StaffLeave.edit' , `_add` = '_view:StaffLeave.add', `_delete` = '_view:StaffLeave.delete' WHERE `controller` = 'Staff' AND `module` = 'Staff' AND `category` = 'Details' AND `name` = 'Leave';
