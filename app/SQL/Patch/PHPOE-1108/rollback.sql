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
		AND `controller` LIKE 'SurveyTemplates'
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

DELETE FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Surveys' AND `title` LIKE 'Questions';

--
-- 2. security_functions
--

DELETE FROM `security_functions` WHERE `controller` LIKE 'SurveyTemplates' AND `category` LIKE 'Surveys' AND `name` LIKE 'Templates';
DELETE FROM `security_functions` WHERE `controller` LIKE 'SurveyQuestions' AND `category` LIKE 'Surveys' AND `name` LIKE 'Questions';
DELETE FROM `security_functions` WHERE `controller` LIKE 'SurveyStatuses' AND `category` LIKE 'Surveys' AND `name` LIKE 'Status';

SET @orderOfAlertsSecurity := 0;
SELECT `order` INTO @orderOfAlertsSecurity FROM `security_functions` WHERE `controller` LIKE 'Alerts' AND `category` LIKE 'Communications' AND `name` LIKE 'Alerts';

UPDATE `security_functions` SET `order` = `order` - 3 WHERE `order` >= @orderOfAlertsSecurity;

--
-- 3. navigations
--

DELETE FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Surveys' AND `title` LIKE 'New';
DELETE FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Surveys' AND `title` LIKE 'Draft';
DELETE FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Surveys' AND `title` LIKE 'Completed';

SET @orderOfRubricsNav := 0;
SELECT `order` INTO @orderOfRubricsNav FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Quality' AND `title` LIKE 'Rubrics';

UPDATE `navigations` SET `order` = `order` - 3 WHERE `order` >= @orderOfRubricsNav;

--
-- 4. security_functions
--

DELETE FROM `security_functions` WHERE `controller` LIKE 'InstitutionSites' AND `category` LIKE 'Surveys' AND `name` LIKE 'New';
DELETE FROM `security_functions` WHERE `controller` LIKE 'InstitutionSites' AND `category` LIKE 'Surveys' AND `name` LIKE 'Completed';

SET @orderOfRubricsSecurity := 0;
SELECT `order` INTO @orderOfRubricsSecurity FROM `security_functions` WHERE `module` LIKE 'Institutions' AND `controller` LIKE 'Quality' AND `category` LIKE 'Quality' AND `name` LIKE 'Rubrics';

UPDATE `security_functions` SET `order` = `order` - 2 WHERE `order` >= @orderOfRubricsSecurity;

--
-- 5. new table: survey_modules
--

DROP TABLE IF EXISTS `survey_modules`;

--
-- 6. new table: survey_questions
--

DROP TABLE IF EXISTS `survey_questions`;

--
-- 7. new table: survey_question_choices
--

DROP TABLE IF EXISTS `survey_question_choices`;

--
-- 8. new table: survey_statuses
--

DROP TABLE IF EXISTS `survey_statuses`;

--
-- 9. new table: survey_status_periods
--

DROP TABLE IF EXISTS `survey_status_periods`;

--
-- 10. new table: survey_table_columns
--

DROP TABLE IF EXISTS `survey_table_columns`;

--
-- 11. new table: survey_table_rows
--

DROP TABLE IF EXISTS `survey_table_rows`;

--
-- 12. new table: survey_templates
--

DROP TABLE IF EXISTS `survey_templates`;

--
-- 13. new table: institution_site_surveys
--

DROP TABLE IF EXISTS `institution_site_surveys`;

--
-- 14. new table: institution_site_survey_answers
--

DROP TABLE IF EXISTS `institution_site_survey_answers`;

--
-- 15. new table: institution_site_survey_table_cells
--

DROP TABLE IF EXISTS `institution_site_survey_table_cells`;
