--
-- 1. Drop new tables
--

DROP TABLE IF EXISTS `wf_workflow_models`;
DROP TABLE IF EXISTS `wf_workflow_records`;
DROP TABLE IF EXISTS `wf_workflow_comments`;

--
-- 2. Alter table wf_workflows
--

ALTER TABLE `wf_workflows` DROP `workflow_model_id`;

--
-- 3. Alter table wf_workflow_steps
--

ALTER TABLE `wf_workflow_steps` CHANGE `workflow_id` `wf_workflow_id` INT(11) NOT NULL;
ALTER TABLE `wf_workflow_steps` DROP `stage`;

--
-- 4. Alter table wf_workflow_actions
--

ALTER TABLE `wf_workflow_actions` CHANGE `workflow_step_id` `wf_workflow_step_id` INT(11) NOT NULL;
ALTER TABLE `wf_workflow_actions` CHANGE `next_workflow_step_id` `next_wf_workflow_step_id` INT(11) NOT NULL;

--
-- 5. Alter table wf_workflow_step_roles
--

ALTER TABLE `wf_workflow_step_roles` CHANGE `workflow_step_id` `wf_workflow_step_id` INT(11) NOT NULL;

--
-- 6. Alter table wf_workflow_transitions -> wf_workflow_logs
--

RENAME TABLE `wf_workflow_transitions` TO `wf_workflow_logs`;
ALTER TABLE `wf_workflow_logs` ADD `reference_table` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `id`;
ALTER TABLE `wf_workflow_logs` ADD `reference_id` INT(11) NOT NULL AFTER `reference_table`;
ALTER TABLE `wf_workflow_logs` ADD `comments` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `reference_id`;
ALTER TABLE `wf_workflow_logs` CHANGE `workflow_step_id` `wf_workflow_step_id` INT(11) NOT NULL;
ALTER TABLE `wf_workflow_logs` DROP `prev_workflow_step_id`;
ALTER TABLE `wf_workflow_logs` DROP `workflow_record_id`;

--
-- 7. Navigations
--

UPDATE `navigations` SET `action` = 'leaves', `pattern` = 'leaves' WHERE `controller` = 'Staff' AND `header` = 'Details' AND `title` = 'Leave';

--
-- 8. Security Functions
--

UPDATE `security_functions` SET `_view` = 'leaves|leavesView', `_edit` = '_view:leavesEdit' , `_add` = '_view:leavesAdd', `_delete` = '_view:leavesDelete' WHERE `controller` = 'Staff' AND `module` = 'Staff' AND `category` = 'Details' AND `name` = 'Leave';
