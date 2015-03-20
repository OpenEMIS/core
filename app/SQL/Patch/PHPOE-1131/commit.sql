--
-- 1. navigations
--

SET @idOfGeneralOverview := 0;
SET @orderOfGeneralOverview := 0;
SELECT `id`, `order` INTO @idOfGeneralOverview, @orderOfGeneralOverview FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'General' AND `title` LIKE 'Overview';

UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` >= @orderOfGeneralOverview;

INSERT INTO `navigations` (
`id` ,
`module` ,
`controller` ,
`header` ,
`title` ,
`action` ,
`pattern` ,
`parent` ,
`is_wizard` ,
`order` ,
`visible` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Institution', 'InstitutionSites', 'General', 'Dashboard', 'dashboard', 'dashboard', '-1', '0', @orderOfGeneralOverview, '1', '1', '0000-00-00 00:00:00'
);

SET @idOfGeneralDashboard := 0;
SELECT `id` INTO @idOfGeneralDashboard FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'General' AND `title` LIKE 'Dashboard';
UPDATE `navigations` SET `parent` = @idOfGeneralDashboard WHERE `module` LIKE 'Institution' AND `header` LIKE 'General' AND `title` LIKE 'Overview';
UPDATE `navigations` SET `parent` = @idOfGeneralDashboard WHERE `parent` = @idOfGeneralOverview;

--
-- 2. security_functions
--

UPDATE `security_functions` SET `_view` = 'index|view|advanced|dashboard' WHERE `name` LIKE 'Institution' AND `controller` LIKE 'InstitutionSites' AND `module` LIKE 'Institutions' AND `category` LIKE 'General';
