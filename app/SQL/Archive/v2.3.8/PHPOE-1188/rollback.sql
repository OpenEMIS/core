--
-- 1. Drop new tables
--

DROP TABLE IF EXISTS `wf_workflow_models`;
DROP TABLE IF EXISTS `wf_workflows`;
DROP TABLE IF EXISTS `wf_workflow_steps`;
DROP TABLE IF EXISTS `wf_workflow_actions`;
DROP TABLE IF EXISTS `wf_workflow_step_roles`;
DROP TABLE IF EXISTS `wf_workflow_records`;
DROP TABLE IF EXISTS `wf_workflow_comments`;
DROP TABLE IF EXISTS `wf_workflow_transitions`;

--
-- 2. Navigations
--

UPDATE `navigations` SET `action` = 'leaves', `pattern` = 'leaves' WHERE `controller` = 'Staff' AND `header` = 'Details' AND `title` = 'Leave';

--
-- 3. Security Functions
--

UPDATE `security_functions` SET `_view` = 'leaves|leavesView', `_edit` = '_view:leavesEdit' , `_add` = '_view:leavesAdd', `_delete` = '_view:leavesDelete' WHERE `controller` = 'Staff' AND `module` = 'Staff' AND `category` = 'Details' AND `name` = 'Leave';

--
-- 3. Update Security Functions for (v2.3.8)
--

UPDATE `security_functions` SET `_delete` = '_view:delete' WHERE `controller` = 'Workflows' AND `module` = 'Administration' AND `category` = 'Workflows' AND `name` = 'Workflows';
UPDATE `security_functions` SET `_delete` = '_view:delete' WHERE `controller` = 'WorkflowSteps' AND `module` = 'Administration' AND `category` = 'Workflows' AND `name` = 'WorkflowSteps';
