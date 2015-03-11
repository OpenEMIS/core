--
-- 1. Drop new tables
--

DROP TABLE IF EXISTS `wf_workflows`;
DROP TABLE IF EXISTS `wf_workflow_steps`;
DROP TABLE IF EXISTS `wf_workflow_step_roles`;
DROP TABLE IF EXISTS `wf_workflow_actions`;
DROP TABLE IF EXISTS `wf_workflow_logs`;

--
-- 2. navigations
--

DELETE FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Workflows' AND `title` LIKE 'Workflows';
DELETE FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Workflows' AND `title` LIKE 'Steps';

SET @orderOfQualityStatus := 0;
SELECT `order` INTO @orderOfQualityStatus FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Quality' AND `title` LIKE 'Status';

UPDATE `navigations` SET `order` = `order` - 2 WHERE `order` > @orderOfQualityStatus;

--
-- 3. security_functions
--

DELETE FROM `security_functions` WHERE `controller` LIKE 'Workflows' AND `category` LIKE 'Workflows' AND `name` LIKE 'Workflows';
DELETE FROM `security_functions` WHERE `controller` LIKE 'WorkflowSteps' AND `category` LIKE 'Workflows' AND `name` LIKE 'WorkflowSteps';

SET @orderOfQualityStatus := 0;
SELECT `order` INTO @orderOfQualityStatus FROM `security_functions` WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Quality' AND `category` LIKE 'Quality' AND `name` LIKE 'Status';

UPDATE `security_functions` SET `order` = `order` - 2 WHERE `order` > @orderOfQualityStatus;
