--
-- 1. navigations
--

UPDATE 	`navigations`
SET 	`plugin` = 'Surveys',
		`controller` = 'SurveyTemplates|SurveyQuestions',
		`header` = 'Surveys',
		`title` = 'Templates',
		`action` = 'index',
		`pattern` = 'index|view|edit|add|delete|reorder|preview'
WHERE 	`module` = 'Administration'
		AND `plugin` LIKE 'Survey'
		AND `controller` LIKE 'Survey'
		AND `header` LIKE 'Survey'
		AND `title` LIKE 'New';

UPDATE 	`navigations`
SET 	`plugin` = 'Surveys',
		`controller` = 'SurveyStatuses',
		`header` = 'Surveys',
		`title` = 'Status',
		`action` = 'index',
		`pattern` = 'index|view|edit|add|delete'
WHERE 	`module` = 'Administration'
		AND `plugin` LIKE 'Survey'
		AND `controller` LIKE 'Survey'
		AND `header` LIKE 'Survey'
		AND `title` LIKE 'Completed';

--
-- 2. security_functions
--

SET @orderOfAlertsSecurity := 0;
SELECT `order` INTO @orderOfAlertsSecurity FROM `security_functions` WHERE `controller` LIKE 'Alerts' AND `category` LIKE 'Communications' AND `name` LIKE 'Alerts';

UPDATE `security_functions` SET `order` = `order` + 2 WHERE `order` >= @orderOfAlertsSecurity;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_view` ,
`_edit` ,
`_add` ,
`_delete` ,
`_execute` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Templates', 'SurveyTemplates', 'Administration', 'Surveys', '-1', 'index|view', '_view:edit', '_view:add', '_view:delete', NULL , @orderOfAlertsSecurity, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

SET @orderOfAlertsSecurity := @orderOfAlertsSecurity + 1;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_view` ,
`_edit` ,
`_add` ,
`_delete` ,
`_execute` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Status', 'SurveyStatuses', 'Administration', 'Surveys', '-1', 'index|view', '_view:edit', '_view:add', '_view:delete', NULL , @orderOfAlertsSecurity, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

--
-- 3. navigations
--

SET @orderOfDashboardsNav := 0;
SELECT `order` INTO @orderOfDashboardsNav FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Reports' AND `title` LIKE 'Dashboards';

UPDATE `navigations` SET `order` = `order` + 3 WHERE `order` >= @orderOfDashboardsNav;

INSERT INTO `navigations` (
`id` ,
`module` ,
`plugin` ,
`controller` ,
`header` ,
`title` ,
`action` ,
`pattern` ,
`attributes` ,
`parent` ,
`is_wizard` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Institution', NULL, 'InstitutionSites', 'Surveys', 'New', 'InstitutionSiteSurveyNew', 'InstitutionSiteSurveyNew', NULL , '3', '0', @orderOfDashboardsNav, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

SET @orderOfDashboardsNav := @orderOfDashboardsNav + 1;

INSERT INTO `navigations` (
`id` ,
`module` ,
`plugin` ,
`controller` ,
`header` ,
`title` ,
`action` ,
`pattern` ,
`attributes` ,
`parent` ,
`is_wizard` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Institution', NULL, 'InstitutionSites', 'Surveys', 'Draft', 'InstitutionSiteSurveyDraft', 'InstitutionSiteSurveyDraft', NULL , '3', '0', @orderOfDashboardsNav, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

SET @orderOfDashboardsNav := @orderOfDashboardsNav + 1;

INSERT INTO `navigations` (
`id` ,
`module` ,
`plugin` ,
`controller` ,
`header` ,
`title` ,
`action` ,
`pattern` ,
`attributes` ,
`parent` ,
`is_wizard` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Institution', NULL, 'InstitutionSites', 'Surveys', 'Completed', 'InstitutionSiteSurveyCompleted', 'InstitutionSiteSurveyCompleted', NULL , '3', '0', @orderOfDashboardsNav, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

--
-- 4. security_functions
--

SET @orderOfDashboardsSecurity := 0;
SELECT `order` INTO @orderOfDashboardsSecurity FROM `security_functions` WHERE `controller` LIKE 'Dashboards' AND `category` LIKE 'Reports' AND `name` LIKE 'Dashboards';

UPDATE `security_functions` SET `order` = `order` + 3 WHERE `order` >= @orderOfDashboardsSecurity;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_view` ,
`_edit` ,
`_add` ,
`_delete` ,
`_execute` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'New', 'InstitutionSites', 'Institutions', 'Surveys', '8', 'InstitutionSiteSurveyNew|InstitutionSiteSurveyNew.index', NULL, '_view:add', NULL, NULL , @orderOfDashboardsSecurity, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

SET @orderOfDashboardsSecurity := @orderOfDashboardsSecurity + 1;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_view` ,
`_edit` ,
`_add` ,
`_delete` ,
`_execute` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Draft', 'InstitutionSites', 'Institutions', 'Surveys', '8', 'InstitutionSiteSurveyDraft|InstitutionSiteSurveyDraft.index', '_view:edit', NULL, '_view:delete', NULL , @orderOfDashboardsSecurity, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

SET @orderOfDashboardsSecurity := @orderOfDashboardsSecurity + 1;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_view` ,
`_edit` ,
`_add` ,
`_delete` ,
`_execute` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Completed', 'InstitutionSites', 'Institutions', 'Surveys', '8', 'InstitutionSiteSurveyCompleted|InstitutionSiteSurveyCompleted.index', NULL, NULL, '_view:delete', NULL , @orderOfDashboardsSecurity, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

--
-- 5.
--