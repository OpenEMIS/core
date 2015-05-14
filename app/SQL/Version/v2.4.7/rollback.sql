-- 
-- PHPOE-783 rollback.sql
-- 

DELETE FROM `contact_types` WHERE `contact_option_id`=(SELECT `id` FROM `contact_options` where `name`='Emergency');
DELETE FROM `contact_options` WHERE `contact_options`.`name` = 'Emergency';
UPDATE `contact_options` SET `contact_options`.`order`=`contact_options`.`order`-1 WHERE `contact_options`.`name` = 'Other';

-- PHPOE-1381
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

--
-- 3. security_functions
--

UPDATE `security_functions` SET `_execute` = NULL WHERE `controller` LIKE 'InstitutionSites' AND `module` LIKE 'Institutions' AND `category` LIKE 'Surveys' AND `name` LIKE 'Completed';

--
-- PHPOE-1387 rollback.sql
-- 

DELETE FROM `security_group_institution_sites`
WHERE `id` IN (SELECT CONCAT_WS('-', `id`, `security_group_id`) FROM `institution_sites`);

ALTER TABLE `security_group_institution_sites` DROP PRIMARY KEY, ADD PRIMARY KEY (`security_group_id`, `institution_site_id`);
ALTER TABLE `security_group_institution_sites` DROP `id`;

DELETE FROM `security_groups`
WHERE `id` IN (SELECT `security_group_id` FROM `institution_sites`);

ALTER TABLE `institution_sites` DROP `security_group_id`;

-- PHPOE-1383
UPDATE `navigations` SET `visible` = 1 WHERE `module` = 'Administration' AND `plugin` = 'Datawarehouse' AND `controller` = 'Datawarehouse' AND `header` = 'Data Processing';
UPDATE `navigations` SET `visible` = 1 WHERE `module` = 'Administration' AND `plugin` = 'DataProcessing' AND `controller` = 'DataProcessing' AND `header` = 'Data Processing';

UPDATE `security_functions` SET `visible` = 1 WHERE `name` = 'Build' AND `controller` = 'DataProcessing' AND `module` = 'Administration' AND `category` = 'Data Processing';
UPDATE `security_functions` SET `visible` = 1 WHERE `name` = 'Generate' AND `controller` = 'DataProcessing' AND `module` = 'Administration' AND `category` = 'Data Processing';
UPDATE `security_functions` SET `visible` = 1 WHERE `name` = 'Export' AND `controller` = 'DataProcessing' AND `module` = 'Administration' AND `category` = 'Data Processing';
UPDATE `security_functions` SET `visible` = 1 WHERE `name` = 'Processes' AND `controller` = 'DataProcessing' AND `module` = 'Administration' AND `category` = 'Data Processing';

-- Update version number
UPDATE `config_items` SET `value` = '2.4.6' WHERE `name` = 'db_version';
