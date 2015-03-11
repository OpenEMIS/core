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
-- 2. Alter table wf_workflows
--

ALTER TABLE `wf_workflows` ADD `wf_workflow_model_id` INT(11) NOT NULL AFTER `name`;

--
-- 3. Alter table wf_workflow_logs
--

ALTER TABLE `wf_workflow_logs` DROP `comments`;
ALTER TABLE `wf_workflow_logs` ADD `wf_prev_workflow_step_id` INT(11) NOT NULL AFTER `reference_id`;
ALTER TABLE `wf_workflow_logs` ADD `wf_workflow_comment_id` INT(11) NOT NULL AFTER `wf_workflow_step_id`;

--
-- 4. Navigations
--

UPDATE `navigations` SET `action` = 'StaffLeave', `pattern` = 'StaffLeave' WHERE `controller` = 'Staff' AND `header` = 'Details' AND `title` = 'Leave';
