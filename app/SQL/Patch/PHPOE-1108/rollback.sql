--
-- 1. navigations
--

UPDATE 	`navigations`
SET 	`plugin` = 'Survey',
		`controller` = 'Survey',
		`header` = 'Survey',
		`title` = 'New',
		`action` = 'index',
		`pattern` = 'index$|^add$|^edit$'
WHERE 	`module` = 'Administration'
		AND `plugin` LIKE 'Surveys'
		AND `controller` LIKE 'SurveyTemplates|SurveyQuestions'
		AND `header` LIKE 'Surveys'
		AND `title` LIKE 'Templates';

UPDATE 	`navigations`
SET 	`plugin` = 'Survey',
		`controller` = 'Survey',
		`header` = 'Survey',
		`title` = 'Completed',
		`action` = 'import',
		`pattern` = 'import$|^synced$'
WHERE 	`module` = 'Administration'
		AND `plugin` LIKE 'Surveys'
		AND `controller` LIKE 'SurveyStatuses'
		AND `header` LIKE 'Surveys'
		AND `title` LIKE 'Status';

--
-- 2. security_functions
--

DELETE FROM `security_functions` WHERE `controller` LIKE 'SurveyTemplates' AND `category` LIKE 'Surveys' AND `name` LIKE 'Templates';
DELETE FROM `security_functions` WHERE `controller` LIKE 'SurveyTemplates' AND `category` LIKE 'Surveys' AND `name` LIKE 'Status';

SET @orderOfAlertsSecurity := 0;
SELECT `order` INTO @orderOfAlertsSecurity FROM `security_functions` WHERE `controller` LIKE 'Alerts' AND `category` LIKE 'Communications' AND `name` LIKE 'Alerts';

UPDATE `security_functions` SET `order` = `order` - 2 WHERE `order` >= @orderOfAlertsSecurity;

--
-- 3. navigations
--

DELETE FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Surveys' AND `title` LIKE 'New';
DELETE FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Surveys' AND `title` LIKE 'Draft';
DELETE FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Surveys' AND `title` LIKE 'Completed';

SET @orderOfDashboardsNav := 0;
SELECT `order` INTO @orderOfDashboardsNav FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Reports' AND `title` LIKE 'Dashboards';

UPDATE `navigations` SET `order` = `order` - 3 WHERE `order` >= @orderOfDashboardsNav;

--
-- 4. security_functions
--

DELETE FROM `security_functions` WHERE `controller` LIKE 'InstitutionSites' AND `category` LIKE 'Surveys' AND `name` LIKE 'New';
DELETE FROM `security_functions` WHERE `controller` LIKE 'InstitutionSites' AND `category` LIKE 'Surveys' AND `name` LIKE 'Draft';
DELETE FROM `security_functions` WHERE `controller` LIKE 'InstitutionSites' AND `category` LIKE 'Surveys' AND `name` LIKE 'Completed';

SET @orderOfDashboardsSecurity := 0;
SELECT `order` INTO @orderOfDashboardsSecurity FROM `security_functions` WHERE `controller` LIKE 'Dashboards' AND `category` LIKE 'Reports' AND `name` LIKE 'Dashboards';

UPDATE `security_functions` SET `order` = `order` - 3 WHERE `order` >= @orderOfDashboardsSecurity;

--
-- 5.
--