-- 
-- PHPOE-783 commit.sql
-- 

INSERT INTO `contact_options` 
(`id`, `name`, `order`, `visible`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT NULL, 'Emergency', `contact_options`.`order` as `order` , '1', NULL, NULL, NULL, NULL, '1', NOW()
FROM `contact_options`
WHERE `contact_options`.`name` = 'Other';

UPDATE `contact_options` SET `contact_options`.`order`=`contact_options`.`order`+1 WHERE `contact_options`.`name` = 'Other';

INSERT INTO `contact_types` 
(`id`, `contact_option_id`, `name`, `order`, `visible`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT 
	NULL, 
	`contact_options`.`id` as `contact_option_id`, 
	'Mother', 
	1, 
	1, 
	NULL, 
	NULL, 
	NULL,
	NULL, 
	1, 
	NOW()
FROM `contact_options`
WHERE `contact_options`.`name` = 'Emergency';

INSERT INTO `contact_types` 
(`id`, `contact_option_id`, `name`, `order`, `visible`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT 
	NULL, 
	`contact_options`.`id` as `contact_option_id`, 
	'Father', 
	2, 
	1, 
	NULL, 
	NULL, 
	NULL,
	NULL, 
	1, 
	NOW()
FROM `contact_options`
WHERE `contact_options`.`name` = 'Emergency';

-- PHPOE-1381
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

--
-- 3. security_functions
--

UPDATE `security_functions` SET `_execute` = '_view:InstitutionSiteSurveyCompleted.excel' WHERE `controller` LIKE 'InstitutionSites' AND `module` LIKE 'Institutions' AND `category` LIKE 'Surveys' AND `name` LIKE 'Completed';

--
-- PHPOE-1387 commit.sql
-- 

ALTER TABLE `institution_sites` ADD `security_group_id` INT(11) NOT NULL DEFAULT '0' AFTER `institution_site_gender_id`,
ADD INDEX `security_group_id` (`security_group_id`) ;

ALTER TABLE `security_group_institution_sites` ADD `id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL FIRST;

SET @id := 0 ;
UPDATE `security_group_institution_sites`
SET `id` = (@id := @id + 1);

ALTER TABLE `security_group_institution_sites` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);


INSERT INTO `security_groups` 
(`name`, `modified_user_id`, `created_user_id`, `created`)
SELECT 	`is`.`name` AS `name`,
		`is`.`id` AS `modified_user_id`,
		'1',
		'0000-00-00 00:00:00'
FROM `institution_sites` AS `is`;

UPDATE `institution_sites` AS `is`
INNER JOIN `security_groups` AS `sg` ON (`is`.`name` = `sg`.`name` AND `is`.`id` = `sg`.`modified_user_id`)
SET `is`.`security_group_id` = `sg`.`id`;

UPDATE `security_groups`
SET `modified_user_id` = NULL, `created` = NOW()
WHERE `created` = '0000-00-00 00:00:00';



INSERT INTO `security_group_institution_sites`
(`id`, `security_group_id`, `institution_site_id`, `created_user_id`, `created`)
SELECT CONCAT_WS('-', `is`.`id`, `is`.`security_group_id`) as `id`
	 , `is`.`security_group_id` AS `security_group_id`
     , `is`.`id` AS `institution_site_id`
     , `is`.`created_user_id` AS `created_user_id`
     , `is`.`created` AS `created`
  FROM `institution_sites` AS `is`;


-- PHPOE-1383
UPDATE `navigations` SET `visible` = 0 WHERE `module` = 'Administration' AND `plugin` = 'Datawarehouse' AND `controller` = 'Datawarehouse' AND `header` = 'Data Processing';
UPDATE `navigations` SET `visible` = 0 WHERE `module` = 'Administration' AND `plugin` = 'DataProcessing' AND `controller` = 'DataProcessing' AND `header` = 'Data Processing';

DELETE FROM `security_functions` WHERE `name` = 'Build' AND `controller` = 'DataProcessing' AND `module` = 'Administration' AND `category` = 'Data Processing';
DELETE FROM `security_functions` WHERE `name` = 'Generate' AND `controller` = 'DataProcessing' AND `module` = 'Administration' AND `category` = 'Data Processing';
DELETE FROM `security_functions` WHERE `name` = 'Export' AND `controller` = 'DataProcessing' AND `module` = 'Administration' AND `category` = 'Data Processing';
DELETE FROM `security_functions` WHERE `name` = 'Processes' AND `controller` = 'DataProcessing' AND `module` = 'Administration' AND `category` = 'Data Processing';


-- Update version number
UPDATE `config_items` SET `value` = '2.4.7' WHERE `name` = 'db_version';
