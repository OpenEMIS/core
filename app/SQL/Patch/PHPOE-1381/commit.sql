--
-- 1. navigations
--

SET @idOfParentNav := 0;
SELECT `id` INTO @idOfParentNav FROM `navigations` WHERE `module` LIKE 'Report' AND `controller` LIKE 'InstitutionReports' AND `header` LIKE 'Institutions' AND `title` LIKE 'List of Reports';

SET @orderOfYearBookNav := 0;
SELECT `order` INTO @orderOfYearBookNav FROM `navigations` WHERE `module` LIKE 'Report' AND `controller` LIKE 'Reports' AND `header` LIKE 'Yearbook' AND `title` LIKE 'General';

UPDATE `navigations` SET `order` = `order` + 2 WHERE `order` >= @orderOfYearBookNav;

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
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Report', 'Surveys', 'SurveyReports', 'Surveys', 'List of Reports', 'index', 'index', NULL , @idOfParentNav, '0', @orderOfYearBookNav, '1', '1', '0000-00-00 00:00:00'
);

SET @orderOfYearBookNav := @orderOfYearBookNav + 1;

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
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Report', 'Surveys', 'SurveyReports', 'Surveys', 'Generate', 'generate', 'generate', NULL , @idOfParentNav, '0', @orderOfYearBookNav, '1', '1', '0000-00-00 00:00:00'
);

--
-- 2. security_functions
--

SET @orderOfYearBookSecurity := 0;
SELECT `order` INTO @orderOfYearBookSecurity FROM `security_functions` WHERE `controller` LIKE 'Reports' AND `module` LIKE 'Reports' AND `category` LIKE 'Yearbook' AND `name` LIKE 'General';

UPDATE `security_functions` SET `order` = `order` + 2 WHERE `order` >= @orderOfYearBookSecurity;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_view` ,
`_execute` ,
`order` ,
`visible` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'List of Reports', 'SurveyReports', 'Reports', 'Surveys', '-1', 'index', 'download', @orderOfYearBookSecurity, '1', '1', '0000-00-00 00:00:00'
);

SET @orderOfYearBookSecurity := @orderOfYearBookSecurity + 1;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_execute` ,
`order` ,
`visible` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Generate', 'SurveyReports', 'Reports', 'Surveys', '-1', 'generate', @orderOfYearBookSecurity, '1', '1', '0000-00-00 00:00:00'
);
