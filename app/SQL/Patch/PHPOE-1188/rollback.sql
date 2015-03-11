--
-- 1. Drop new tables
--

DROP TABLE IF EXISTS `wf_workflow_models`;

--
-- 2. Alter table wf_workflows
--

ALTER TABLE `wf_workflows` DROP `wf_workflow_model_id`;

--
-- 3. Alter table wf_workflow_logs
--

ALTER TABLE `wf_workflow_logs` ADD `comments` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `reference_id`;
ALTER TABLE `wf_workflow_logs` DROP `wf_prev_workflow_step_id`;
ALTER TABLE `wf_workflow_logs` DROP `wf_workflow_comment_id`;

--
-- 4. Navigations
--

UPDATE `navigations` SET `action` = 'leaves', `pattern` = 'leaves' WHERE `controller` = 'Staff' AND `header` = 'Details' AND `title` = 'Leave';
