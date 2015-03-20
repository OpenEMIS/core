--
-- 1. navigations
--

SET @idOfGeneralDashboard := 0;
SELECT `id` INTO @idOfGeneralDashboard FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'General' AND `title` LIKE 'Dashboard';

SET @idOfGeneralOverview := 0;
SET @orderOfGeneralOverview := 0;
SELECT `id`, `order` INTO @idOfGeneralOverview, @orderOfGeneralOverview FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'General' AND `title` LIKE 'Overview';

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` >= @orderOfGeneralOverview;

UPDATE `navigations` SET `parent` = '-1' WHERE `module` LIKE 'Institution' AND `header` LIKE 'General' AND `title` LIKE 'Overview';
UPDATE `navigations` SET `parent` = @idOfGeneralOverview WHERE `parent` = @idOfGeneralDashboard;

DELETE FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'General' AND `title` LIKE 'Dashboard';

--
-- 2. security_functions
--

UPDATE `security_functions` SET `_view` = 'index|view|advanced' WHERE `name` LIKE 'Institution' AND `controller` LIKE 'InstitutionSites' AND `module` LIKE 'Institutions' AND `category` LIKE 'General';
