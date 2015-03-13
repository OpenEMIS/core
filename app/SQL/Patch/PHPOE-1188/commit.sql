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

INSERT INTO `tst_openemis_core`.`wf_workflow_models` (`id`, `model`, `created_user_id`, `created`) VALUES (NULL, 'StaffLeave', '1', '0000-00-00 00:00:00');

--
-- 2. New table - wf_workflow_records
--

DROP TABLE IF EXISTS `wf_workflow_records`;
CREATE TABLE IF NOT EXISTS `wf_workflow_records` (
`id` int(11) NOT NULL,
  `model` varchar(200) NOT NULL,
  `model_reference` int(11) NOT NULL,
  `stage` int(1) NOT NULL DEFAULT '1' COMMENT '0 -> Open, 1 -> Closed',
  `workflow_id` int(11) NOT NULL,
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
-- 3. New table - wf_workflow_comments
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
-- 4. Alter table wf_workflows
--

ALTER TABLE `wf_workflows` ADD `workflow_model_id` INT(11) NOT NULL AFTER `name`;

--
-- 5. Alter table wf_workflow_steps
--

ALTER TABLE `wf_workflow_steps` CHANGE `wf_workflow_id` `workflow_id` INT(11) NOT NULL;
ALTER TABLE `wf_workflow_steps` ADD `stage` INT(1) NULL DEFAULT NULL COMMENT '0 -> Open, 1 -> Closed' AFTER `name`;

--
-- 6. Alter table wf_workflow_actions
--

ALTER TABLE `wf_workflow_actions` CHANGE `wf_workflow_step_id` `workflow_step_id` INT(11) NOT NULL;
ALTER TABLE `wf_workflow_actions` CHANGE `next_wf_workflow_step_id` `next_workflow_step_id` INT(11) NOT NULL;

--
-- 7. Alter table wf_workflow_step_roles
--

ALTER TABLE `wf_workflow_step_roles` CHANGE `wf_workflow_step_id` `workflow_step_id` INT(11) NOT NULL;

--
-- 8. Alter table wf_workflow_logs -> wf_workflow_transitions
--

RENAME TABLE `wf_workflow_logs` TO `wf_workflow_transitions`;
ALTER TABLE `wf_workflow_transitions` DROP `reference_table`;
ALTER TABLE `wf_workflow_transitions` DROP `reference_id`;
ALTER TABLE `wf_workflow_transitions` DROP `comments`;
ALTER TABLE `wf_workflow_transitions` CHANGE `wf_workflow_step_id` `workflow_step_id` INT(11) NOT NULL;
ALTER TABLE `wf_workflow_transitions` ADD `prev_workflow_step_id` INT(11) NOT NULL AFTER `id`;
ALTER TABLE `wf_workflow_transitions` ADD `workflow_record_id` INT(11) NOT NULL AFTER `workflow_step_id`;

--
-- 9. Navigations
--

UPDATE `navigations` SET `action` = 'StaffLeave', `pattern` = 'StaffLeave' WHERE `controller` = 'Staff' AND `header` = 'Details' AND `title` = 'Leave';

--
-- 10. Security Functions
--

UPDATE `security_functions` SET `_view` = 'StaffLeave|StaffLeave.index', `_edit` = '_view:StaffLeave.edit' , `_add` = '_view:StaffLeave.add', `_delete` = '_view:StaffLeave.delete' WHERE `controller` = 'Staff' AND `module` = 'Staff' AND `category` = 'Details' AND `name` = 'Leave';
