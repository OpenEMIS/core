UPDATE `navigations` 
SET `plugin` = 'Dashboards', `controller` = 'Dashboards', `action` = 'dashboardReport', `pattern` = 'dashboardReport'
WHERE `header` = 'Dashboards' AND `title` = 'General';

UPDATE `navigations` 
SET `plugin` = 'Dashboards', `action` = 'general', `pattern` = 'general'
WHERE `module` = 'Institution' AND `controller` = 'Dashboards';

UPDATE `security_functions`
SET `name` = 'Dashboards', `module` = 'Institutions', `category` = 'Reports', `_view` = 'InstitutionQA|general'
WHERE `controller` = 'Dashboards' AND `parent_id` = 8;

DELETE FROM `security_functions` WHERE `controller` = 'Reports' AND `category` = 'Dashboards';
DELETE FROM `security_functions` WHERE `controller` = 'Dashboards' AND `category` = 'Dashboards';

SET @maxOrder := 0;
SELECT MAX(`id`) + 1 INTO @maxOrder FROM `security_functions`;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(NULL, 'General', 'Dashboards', 'Reports', 'Dashboards', -1, 'overview|dashboardReport|dashboards', NULL, NULL, NULL, 'genCSV', @maxOrder, 1, 1, '0000-00-00 00:00:00');

UPDATE `security_functions` SET `order` = @maxOrder+1, `category` = 'Visualizer' WHERE `controller` = 'Visualizer' AND `module` = 'Visualizer';
