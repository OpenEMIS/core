--
-- 1. navigations
--

UPDATE 	`navigations`
SET 	`plugin` = 'Surveys',
		`controller` = 'SurveyTemplates',
		`header` = 'Surveys',
		`title` = 'Templates',
		`action` = 'index',
		`pattern` = 'index|view|edit|add|delete'
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

SET @orderOfStatusNav := 0;
SELECT `order` INTO @orderOfStatusNav FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Surveys' AND `title` LIKE 'Status';

UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` >= @orderOfStatusNav;

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
NULL , 'Administration', 'Surveys', 'SurveyQuestions', 'Surveys', 'Questions', 'index', 'index|view|edit|add|delete|reorder|preview', NULL , '33', '0', @orderOfStatusNav, '1', '1', '0000-00-00 00:00:00'
);

--
-- 2. security_functions
--

SET @orderOfAlertsSecurity := 0;
SELECT `order` INTO @orderOfAlertsSecurity FROM `security_functions` WHERE `controller` LIKE 'Alerts' AND `category` LIKE 'Communications' AND `name` LIKE 'Alerts';

UPDATE `security_functions` SET `order` = `order` + 3 WHERE `order` >= @orderOfAlertsSecurity;

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
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Templates', 'SurveyTemplates', 'Administration', 'Surveys', '-1', 'index|view', '_view:edit', '_view:add', '_view:delete', NULL , @orderOfAlertsSecurity, '1', '1', '0000-00-00 00:00:00'
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
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Questions', 'SurveyQuestions', 'Administration', 'Surveys', '-1', 'index|view', '_view:edit', '_view:add', '_view:delete', NULL , @orderOfAlertsSecurity, '1', '1', '0000-00-00 00:00:00'
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
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Status', 'SurveyStatuses', 'Administration', 'Surveys', '-1', 'index|view', '_view:edit', '_view:add', '_view:delete', NULL , @orderOfAlertsSecurity, '1', '1', '0000-00-00 00:00:00'
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
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Institution', NULL, 'InstitutionSites', 'Surveys', 'New', 'InstitutionSiteSurveyNew', 'InstitutionSiteSurveyNew', NULL , '3', '0', @orderOfDashboardsNav, '1', '1', '0000-00-00 00:00:00'
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
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Institution', NULL, 'InstitutionSites', 'Surveys', 'Draft', 'InstitutionSiteSurveyDraft', 'InstitutionSiteSurveyDraft', NULL , '3', '0', @orderOfDashboardsNav, '1', '1', '0000-00-00 00:00:00'
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
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Institution', NULL, 'InstitutionSites', 'Surveys', 'Completed', 'InstitutionSiteSurveyCompleted', 'InstitutionSiteSurveyCompleted', NULL , '3', '0', @orderOfDashboardsNav, '1', '1', '0000-00-00 00:00:00'
);

--
-- 4. security_functions
--

SET @orderOfDashboardsSecurity := 0;
SELECT `order` INTO @orderOfDashboardsSecurity FROM `security_functions` WHERE `controller` LIKE 'Dashboards' AND `category` LIKE 'Reports' AND `name` LIKE 'Dashboards';

UPDATE `security_functions` SET `order` = `order` + 2 WHERE `order` >= @orderOfDashboardsSecurity;

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
`created_user_id` ,
`created`
)
VALUES (
NULL , 'New', 'InstitutionSites', 'Institutions', 'Surveys', '8', 'InstitutionSiteSurveyNew|InstitutionSiteSurveyNew.index|InstitutionSiteSurveyNew.view|InstitutionSiteSurveyDraft|InstitutionSiteSurveyDraft.index|InstitutionSiteSurveyDraft.view', '_view:InstitutionSiteSurveyDraft.edit', '_view:InstitutionSiteSurveyNew.add', '_view:InstitutionSiteSurveyDraft.remove', NULL , @orderOfDashboardsSecurity, '1', '1', '0000-00-00 00:00:00'
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
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Completed', 'InstitutionSites', 'Institutions', 'Surveys', '8', 'InstitutionSiteSurveyCompleted|InstitutionSiteSurveyCompleted.index|InstitutionSiteSurveyCompleted.view|InstitutionSiteSurveyCompleted.details', NULL, NULL, '_view:InstitutionSiteSurveyCompleted.remove', NULL , @orderOfDashboardsSecurity, '1', '1', '0000-00-00 00:00:00'
);

--
-- 5. new table: survey_modules
--

CREATE TABLE IF NOT EXISTS `survey_modules` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


ALTER TABLE `survey_modules`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `survey_modules`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

TRUNCATE TABLE `survey_modules`;
INSERT INTO `survey_modules` (`id`, `name`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'Institution', 1, 1, NULL, NULL, 1, '2014-12-17 18:00:00'),
(2, 'Student', 2, 1, NULL, NULL, 1, '2014-12-17 18:00:00'),
(3, 'Staff', 3, 1, NULL, NULL, 1, '2014-12-17 18:00:00'),
(4, 'InstitutionSiteStudent', 4, 0, NULL, NULL, 1, '2014-12-17 18:00:00'),
(5, 'InstitutionSiteStaff', 5, 0, NULL, NULL, 1, '2014-12-17 18:00:00');

--
-- 6. new table: survey_questions
--

CREATE TABLE IF NOT EXISTS `survey_questions` (
`id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL,
  `type` int(1) NOT NULL COMMENT '1 -> Label, 2 -> Text, 3 -> Dropdown, 4 -> Checkbox, 5 -> Textarea, 6 -> Number, 7 -> Table',
  `is_mandatory` int(1) NOT NULL,
  `is_unique` int(1) NOT NULL,
  `visible` int(1) NOT NULL,
  `survey_template_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


ALTER TABLE `survey_questions`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `survey_questions`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

--
-- 7. new table: survey_question_choices
--

CREATE TABLE IF NOT EXISTS `survey_question_choices` (
  `id` char(36) NOT NULL,
  `value` varchar(250) NOT NULL,
  `default_choice` int(1) NOT NULL COMMENT '0 -> No, 1 -> Yes',
  `order` int(2) NOT NULL,
  `visible` int(1) NOT NULL,
  `international_code` varchar(50) NOT NULL,
  `national_code` varchar(50) NOT NULL,
  `survey_question_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `survey_question_choices`
 ADD PRIMARY KEY (`id`);

--
-- 8. new table: survey_statuses
--

CREATE TABLE IF NOT EXISTS `survey_statuses` (
`id` int(11) NOT NULL,
  `date_enabled` date NOT NULL,
  `date_disabled` date NOT NULL,
  `academic_period_type_id` int(11) NOT NULL,
  `survey_template_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


ALTER TABLE `survey_statuses`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `survey_statuses`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

--
-- 9. new table: survey_status_periods
--

DROP TABLE IF EXISTS `survey_status_periods`;
CREATE TABLE IF NOT EXISTS `survey_status_periods` (
  `id` char(36) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `survey_status_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `survey_status_periods`
 ADD PRIMARY KEY (`id`);

--
-- 10. new table: survey_table_columns
--

CREATE TABLE IF NOT EXISTS `survey_table_columns` (
`id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL,
  `survey_question_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


ALTER TABLE `survey_table_columns`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `survey_table_columns`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

--
-- 11. new table: survey_table_rows
--

CREATE TABLE IF NOT EXISTS `survey_table_rows` (
`id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL,
  `survey_question_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


ALTER TABLE `survey_table_rows`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `survey_table_rows`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

--
-- 12. new table: survey_templates
--

CREATE TABLE IF NOT EXISTS `survey_templates` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `survey_module_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


ALTER TABLE `survey_templates`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `survey_templates`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

--
-- 13. new table: institution_site_surveys
--

DROP TABLE IF EXISTS `institution_site_surveys`;
CREATE TABLE IF NOT EXISTS `institution_site_surveys` (
`id` int(11) NOT NULL,
  `status` int(1) NOT NULL COMMENT '0 -> New, 1 -> Draft, 2 -> Completed',
  `academic_period_id` int(11) NOT NULL,
  `survey_status_id` int(11) NOT NULL,
  `survey_template_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_surveys`
 ADD PRIMARY KEY (`id`), ADD KEY `institution_site_id` (`institution_site_id`);


ALTER TABLE `institution_site_surveys`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

--
-- 14. new table: institution_site_survey_answers
--

CREATE TABLE IF NOT EXISTS `institution_site_survey_answers` (
  `institution_site_survey_id` int(11) NOT NULL,
  `survey_question_id` int(11) NOT NULL,
  `answer_number` int(2) NOT NULL,
  `text_value` varchar(250) NOT NULL,
  `int_value` int(11) DEFAULT NULL,
  `textarea_value` text NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) NOT NULL,
  `modified` datetime NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_survey_answers`
 ADD PRIMARY KEY (`institution_site_survey_id`,`survey_question_id`,`answer_number`), ADD KEY `text_value` (`text_value`), ADD KEY `int_value` (`int_value`), ADD KEY `institution_site_id` (`institution_site_id`);

--
-- 15. new table: institution_site_survey_table_cells
--

CREATE TABLE IF NOT EXISTS `institution_site_survey_table_cells` (
  `institution_site_survey_id` int(11) NOT NULL,
  `survey_question_id` int(11) NOT NULL,
  `survey_table_column_id` int(11) NOT NULL,
  `survey_table_row_id` int(11) NOT NULL,
  `value` varchar(250) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) NOT NULL,
  `modified` datetime NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_survey_table_cells`
 ADD PRIMARY KEY (`institution_site_survey_id`,`survey_question_id`,`survey_table_column_id`,`survey_table_row_id`), ADD KEY `institution_site_id` (`institution_site_id`);

--
-- 16. new table: academic_period_types
--

CREATE TABLE IF NOT EXISTS `academic_period_types` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


ALTER TABLE `academic_period_types`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `academic_period_types`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;


TRUNCATE TABLE `academic_period_types`;
INSERT INTO `academic_period_types` (`id`, `name`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'Yearly', 1, 1, NULL, NULL, 1, '2014-12-17 12:00:00');
