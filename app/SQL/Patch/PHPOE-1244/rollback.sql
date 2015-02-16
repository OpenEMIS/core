--
-- 1. Drop new tables
--

DROP TABLE IF EXISTS `workflows`;
DROP TABLE IF EXISTS `workflow_steps`;
DROP TABLE IF EXISTS `workflow_step_roles`;
DROP TABLE IF EXISTS `workflow_actions`;
DROP TABLE IF EXISTS `workflow_logs`;

--
-- 2. Restore
--

RENAME TABLE 1244_workflows TO workflows;
RENAME TABLE 1244_workflow_steps TO workflow_steps;
RENAME TABLE 1244_workflow_logs TO workflow_logs;

--
-- 3. navigations
--

DELETE FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Workflows' AND `title` LIKE 'Workflows';
DELETE FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Workflows' AND `title` LIKE 'Steps';
DELETE FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Workflows' AND `title` LIKE 'Logs';

SET @orderOfQualityStatus := 0;
SELECT `order` INTO @orderOfQualityStatus FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Quality' AND `title` LIKE 'Status';

UPDATE `navigations` SET `order` = `order` - 3 WHERE `order` > @orderOfQualityStatus;

--
-- 4. security_functions
--

DELETE FROM `security_functions` WHERE `controller` LIKE 'Workflows' AND `category` LIKE 'Workflows' AND `name` LIKE 'Workflows';
DELETE FROM `security_functions` WHERE `controller` LIKE 'WorkflowSteps' AND `category` LIKE 'Workflows' AND `name` LIKE 'WorkflowSteps';
DELETE FROM `security_functions` WHERE `controller` LIKE 'WorkflowLogs' AND `category` LIKE 'Workflows' AND `name` LIKE 'WorkflowLogs';

SET @orderOfQualityStatus := 0;
SELECT `order` INTO @orderOfQualityStatus FROM `security_functions` WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Quality' AND `category` LIKE 'Quality' AND `name` LIKE 'Status';

UPDATE `security_functions` SET `order` = `order` - 3 WHERE `order` > @orderOfQualityStatus;
