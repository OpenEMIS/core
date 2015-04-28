--
-- 1. navigations
--

DELETE FROM `navigations` WHERE `module` LIKE 'Report' AND `controller` LIKE 'SurveyReports' AND `header` LIKE 'Surveys' AND `title` LIKE 'List of Reports';
DELETE FROM `navigations` WHERE `module` LIKE 'Report' AND `controller` LIKE 'SurveyReports' AND `header` LIKE 'Surveys' AND `title` LIKE 'Generate';

SET @orderOfYearBookNav := 0;
SELECT `order` INTO @orderOfYearBookNav FROM `navigations` WHERE `module` LIKE 'Report' AND `controller` LIKE 'Reports' AND `header` LIKE 'Yearbook' AND `title` LIKE 'General';

UPDATE `navigations` SET `order` = `order` - 2 WHERE `order` >= @orderOfYearBookNav;

--
-- 2. security_functions
--

DELETE FROM `security_functions` WHERE `controller` LIKE 'SurveyReports' AND `module` LIKE 'Reports' AND `category` LIKE 'Surveys' AND `name` LIKE 'List of Reports';
DELETE FROM `security_functions` WHERE `controller` LIKE 'SurveyReports' AND `module` LIKE 'Reports' AND `category` LIKE 'Surveys' AND `name` LIKE 'Generate';

SET @orderOfYearBookSecurity := 0;
SELECT `order` INTO @orderOfYearBookSecurity FROM `security_functions` WHERE `controller` LIKE 'Reports' AND `module` LIKE 'Reports' AND `category` LIKE 'Yearbook' AND `name` LIKE 'General';

UPDATE `security_functions` SET `order` = `order` - 2 WHERE `order` >= @orderOfYearBookSecurity;
