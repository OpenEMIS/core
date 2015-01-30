-- PHPOE-924 (Infrastructure)

--
-- 1. navigations
--

ALTER TABLE `navigations` CHANGE `controller` `controller` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `navigations` CHANGE `pattern` `pattern` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

SET @orderEduStructure := 0;
SELECT `order` INTO @orderEduStructure FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'System Setup' AND `title` LIKE 'Education Structure';

UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` > @orderEduStructure;

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
VALUES (NULL , 'Administration', 'Infrastructure' , 'InfrastructureLevels|InfrastructureTypes|InfrastructureCustomFields', 'System Setup', 'Infrastructure', 'index', 'index|view|add|edit|delete|remove|reorder|preview', NULL , '33', '0', @orderEduStructure + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00');

SET @orderDetailsClasses := 0;
SELECT `order` INTO @orderDetailsClasses FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Details' AND `title` LIKE 'Classes';

UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` > @orderDetailsClasses;

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
VALUES (NULL , 'Institution', NULL , 'InstitutionSites', 'Details', 'Infrastructure', 'InstitutionSiteInfrastructure', 'InstitutionSiteInfrastructure|InstitutionSiteInfrastructure.index|InstitutionSiteInfrastructure.view|InstitutionSiteInfrastructure.add|InstitutionSiteInfrastructure.edit|InstitutionSiteInfrastructure.remove', NULL , '3', '0', @orderDetailsClasses + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00');

--
-- 2. security_functions
--

SET @orderDetailsClassesSecurity := 0;
SELECT `order` INTO @orderDetailsClassesSecurity FROM `security_functions` WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Details' AND `name` LIKE 'Classes';

UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` > @orderDetailsClassesSecurity;

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
NULL , 'Infrastructure', 'InstitutionSites', 'Institutions', 'Details', '8', 'InstitutionSiteInfrastructure|InstitutionSiteInfrastructure.index|InstitutionSiteInfrastructure.view', '_view:InstitutionSiteInfrastructure.edit', '_view:InstitutionSiteInfrastructure.add', '_view:InstitutionSiteInfrastructure.remove', NULL , @orderDetailsClassesSecurity + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

SET @orderEduProgSecurity := 0;
SELECT `order` INTO @orderEduProgSecurity FROM `security_functions` WHERE `module` LIKE 'Administration' AND `category` LIKE 'Education' AND `name` LIKE 'Education Programme Orientations';

UPDATE `security_functions` SET `order` = `order` + 3 WHERE `order` > @orderEduProgSecurity;

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
) VALUES  
(NULL , 'Levels', 'InfrastructureLevels', 'Administration', 'Infrastructure', '-1', 'index|view', '_view:edit', '_view:add', '_view:remove', NULL , @orderEduProgSecurity + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00');

SET @securityInfraCatId := 0;
SELECT `id` INTO @securityInfraCatId FROM `security_functions` WHERE `module` LIKE 'Administration' AND `category` LIKE 'Infrastructure' AND `name` LIKE 'Levels';

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
) VALUES 
(NULL , 'Types', 'InfrastructureTypes', 'Administration', 'Infrastructure', @securityInfraCatId, 'index|view', '_view:edit', '_view:add', '_view:remove', NULL , @orderEduProgSecurity + 2, '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Custom Fields', 'InfrastructureCustomFields', 'Administration', 'Infrastructure', @securityInfraCatId, 'index|view', '_view:edit', '_view:add', '_view:remove', NULL , @orderEduProgSecurity + 3, '1', NULL , NULL , '1', '0000-00-00 00:00:00');

--
-- 3. Table structure for table `infrastructure_levels`
--

CREATE TABLE `infrastructure_levels` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `international_code` varchar(10) DEFAULT NULL,
  `national_code` varchar(10) DEFAULT NULL,
  `parent_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `infrastructure_levels`
--

INSERT INTO `infrastructure_levels` (`id`, `name`, `order`, `visible`, `international_code`, `national_code`, `parent_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'Property', 0, 1, NULL, NULL, 0, NULL, NULL, 1, '2015-01-23 04:46:57'),
(2, 'Building', 0, 1, NULL, NULL, 1, NULL, NULL, 1, '2015-01-23 04:47:05'),
(3, 'Room', 0, 1, NULL, NULL, 2, NULL, NULL, 1, '2015-01-23 04:47:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `infrastructure_levels`
--
ALTER TABLE `infrastructure_levels`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `infrastructure_levels`
--
ALTER TABLE `infrastructure_levels`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;

--
-- 4. Table structure for table `infrastructure_types`
--

CREATE TABLE `infrastructure_types` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `order` int(3) NOT NULL,
  `infrastructure_level_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Indexes for table `infrastructure_types`
--
ALTER TABLE `infrastructure_types`
 ADD PRIMARY KEY (`id`), ADD KEY `infrastructure_level_id` (`infrastructure_level_id`);


--
-- AUTO_INCREMENT for table `infrastructure_types`
--
ALTER TABLE `infrastructure_types`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;


--
-- 5. Table structure for table `institution_site_infrastructures`
--

--
-- Table structure for table `institution_site_infrastructures`
--

CREATE TABLE `institution_site_infrastructures` (
`id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `code` varchar(50) NOT NULL,
  `year_acquired` year(4) DEFAULT NULL,
  `year_disposed` year(4) DEFAULT NULL,
  `comment` text,
  `size` float DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `institution_site_id` int(11) NOT NULL,
  `infrastructure_level_id` int(11) NOT NULL,
  `infrastructure_type_id` int(11) NOT NULL,
  `infrastructure_ownership_id` int(11) NOT NULL,
  `infrastructure_condition_id` int(11) NOT NULL,
  `modified_user_id` int(5) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(5) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `institution_site_infrastructures`
--
ALTER TABLE `institution_site_infrastructures`
 ADD PRIMARY KEY (`id`), ADD KEY `name` (`name`), ADD KEY `code` (`code`), ADD KEY `infrastructure_level_id` (`infrastructure_level_id`), ADD KEY `infrastructure_type_id` (`infrastructure_type_id`), ADD KEY `infrastructure_ownership_id` (`infrastructure_ownership_id`), ADD KEY `institution_site_id` (`institution_site_id`), ADD KEY `parent_id` (`parent_id`), ADD KEY `infrastructure_condition_id` (`infrastructure_condition_id`);



--
-- AUTO_INCREMENT for table `institution_site_infrastructures`
--
ALTER TABLE `institution_site_infrastructures`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

--
-- 6. new field option `InfrastructureOwnership`
--

SET @maxFieldOptionOrder := 0;

SELECT MAX(`order`) INTO @maxFieldOptionOrder FROM `field_options`;

INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
(NULL, 'InfrastructureOwnership', 'Ownership', 'Infrastructure', NULL, @maxFieldOptionOrder + 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

--
-- 7. new field option `InfrastructureCondition`
--

SET @maxFieldOptionOrder := 0;

SELECT MAX(`order`) INTO @maxFieldOptionOrder FROM `field_options`;

INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
(NULL, 'InfrastructureCondition', 'Condition', 'Infrastructure', NULL, @maxFieldOptionOrder + 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

--
-- 8. Table structure for table `infrastructure_custom_fields`
--


CREATE TABLE `infrastructure_custom_fields` (
`id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '1 -> Label, 2 -> Text, 3 -> Dropdown, 4 -> Checkbox, 5 -> Textarea, 6 -> Number, 7 -> Table',
  `is_mandatory` int(1) DEFAULT '0',
  `is_unique` int(1) DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `infrastructure_level_id` int(11) NOT NULL DEFAULT '0',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


--
-- Indexes for table `infrastructure_custom_fields`
--
ALTER TABLE `infrastructure_custom_fields`
 ADD PRIMARY KEY (`id`), ADD KEY `infrastructure_level_id` (`infrastructure_level_id`);


--
-- AUTO_INCREMENT for table `infrastructure_custom_fields`
--
ALTER TABLE `infrastructure_custom_fields`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

--
-- 9. Table structure for table `infrastructure_custom_field_options`
--


CREATE TABLE `infrastructure_custom_field_options` (
`id` int(11) NOT NULL,
  `value` varchar(250) NOT NULL,
  `default_option` int(1) DEFAULT '0',
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `infrastructure_custom_field_id` int(11) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Indexes for table `infrastructure_custom_field_options`
--
ALTER TABLE `infrastructure_custom_field_options`
 ADD PRIMARY KEY (`id`), ADD KEY `infrastructure_custom_field_id` (`infrastructure_custom_field_id`);


--
-- AUTO_INCREMENT for table `infrastructure_custom_field_options`
--
ALTER TABLE `infrastructure_custom_field_options`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

--
-- 10. Table structure for table `institution_site_infrastructure_custom_values`
--


CREATE TABLE `institution_site_infrastructure_custom_values` (
  `text_value` varchar(250) NOT NULL,
  `int_value` int(11) NOT NULL,
  `textarea_value` text NOT NULL,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `institution_site_infrastructure_id` int(11) NOT NULL,
  `value_number` int(2) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Indexes for table `institution_site_infrastructure_custom_values`
--
ALTER TABLE `institution_site_infrastructure_custom_values`
 ADD PRIMARY KEY (`infrastructure_custom_field_id`,`institution_site_infrastructure_id`,`value_number`), ADD KEY `int_value` (`int_value`), ADD KEY `institution_site_id` (`institution_site_id`), ADD KEY `text_value` (`text_value`);

-- PHPOE-1108 (Survey)

--
-- 1. navigations
--

UPDATE  `navigations`
SET   `plugin` = 'Surveys',
    `controller` = 'SurveyTemplates',
    `header` = 'Surveys',
    `title` = 'Templates',
    `action` = 'index',
    `pattern` = 'index|view|edit|add|delete'
WHERE   `module` = 'Administration'
    AND `plugin` LIKE 'Survey'
    AND `controller` LIKE 'Survey'
    AND `header` LIKE 'Survey'
    AND `title` LIKE 'New';

UPDATE  `navigations`
SET   `plugin` = 'Surveys',
    `controller` = 'SurveyStatuses',
    `header` = 'Surveys',
    `title` = 'Status',
    `action` = 'index',
    `pattern` = 'index|view|edit|add|delete'
WHERE   `module` = 'Administration'
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
NULL , 'Questions', 'SurveyQuestions', 'Administration', 'Surveys', '-1', 'index|view|preview|listing|download', '_view:edit|reorder|moveOrder', '_view:add', '_view:delete', NULL , @orderOfAlertsSecurity, '1', '1', '0000-00-00 00:00:00'
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

SET @orderOfRubricsNav := 0;
SELECT `order` INTO @orderOfRubricsNav FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Quality' AND `title` LIKE 'Rubrics';

UPDATE `navigations` SET `order` = `order` + 3 WHERE `order` >= @orderOfRubricsNav;

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
NULL , 'Institution', NULL, 'InstitutionSites', 'Surveys', 'New', 'InstitutionSiteSurveyNew', 'InstitutionSiteSurveyNew', NULL , '3', '0', @orderOfRubricsNav, '1', '1', '0000-00-00 00:00:00'
);

SET @orderOfRubricsNav := @orderOfRubricsNav + 1;

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
NULL , 'Institution', NULL, 'InstitutionSites', 'Surveys', 'Draft', 'InstitutionSiteSurveyDraft', 'InstitutionSiteSurveyDraft', NULL , '3', '0', @orderOfRubricsNav, '1', '1', '0000-00-00 00:00:00'
);

SET @orderOfRubricsNav := @orderOfRubricsNav + 1;

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
NULL , 'Institution', NULL, 'InstitutionSites', 'Surveys', 'Completed', 'InstitutionSiteSurveyCompleted', 'InstitutionSiteSurveyCompleted', NULL , '3', '0', @orderOfRubricsNav, '1', '1', '0000-00-00 00:00:00'
);

--
-- 4. security_functions
--

SET @orderOfRubricsSecurity := 0;
SELECT `order` INTO @orderOfRubricsSecurity FROM `security_functions` WHERE `module` LIKE 'Institutions' AND `controller` LIKE 'Quality' AND `category` LIKE 'Quality' AND `name` LIKE 'Rubrics';

UPDATE `security_functions` SET `order` = `order` + 2 WHERE `order` >= @orderOfRubricsSecurity;

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
NULL , 'New', 'InstitutionSites', 'Institutions', 'Surveys', '8', 'InstitutionSiteSurveyNew|InstitutionSiteSurveyNew.index|InstitutionSiteSurveyNew.view|InstitutionSiteSurveyDraft|InstitutionSiteSurveyDraft.index|InstitutionSiteSurveyDraft.view', '_view:InstitutionSiteSurveyDraft.edit', '_view:InstitutionSiteSurveyNew.add', '_view:InstitutionSiteSurveyDraft.remove', NULL , @orderOfRubricsSecurity, '1', '1', '0000-00-00 00:00:00'
);

SET @orderOfRubricsSecurity := @orderOfRubricsSecurity + 1;

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
NULL , 'Completed', 'InstitutionSites', 'Institutions', 'Surveys', '8', 'InstitutionSiteSurveyCompleted|InstitutionSiteSurveyCompleted.index|InstitutionSiteSurveyCompleted.view', NULL, NULL, '_view:InstitutionSiteSurveyCompleted.remove', NULL , @orderOfRubricsSecurity, '1', '1', '0000-00-00 00:00:00'
);

--
-- 5. new table: survey_modules
--

CREATE TABLE IF NOT EXISTS `survey_modules` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
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
(1, 'Institution', 1, 1, NULL, NULL, 1, '2014-12-17 18:00:00');
-- (2, 'Student', 2, 1, NULL, NULL, 1, '2014-12-17 18:00:00'),
-- (3, 'Staff', 3, 1, NULL, NULL, 1, '2014-12-17 18:00:00'),
-- (4, 'InstitutionSiteStudent', 4, 0, NULL, NULL, 1, '2014-12-17 18:00:00'),
-- (5, 'InstitutionSiteStaff', 5, 0, NULL, NULL, 1, '2014-12-17 18:00:00');

--
-- 6. new table: survey_questions
--

CREATE TABLE IF NOT EXISTS `survey_questions` (
`id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '1 -> Label, 2 -> Text, 3 -> Dropdown, 4 -> Checkbox, 5 -> Textarea, 6 -> Number, 7 -> Table',
  `is_mandatory` int(1) DEFAULT '0',
  `is_unique` int(1) DEFAULT '0',
  `visible` int(1) DEFAULT '1',
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
`id` int(11) NOT NULL,
  `value` varchar(250) NOT NULL,
  `default_option` int(1) DEFAULT '0' COMMENT '0 -> No, 1 -> Yes',
  `order` int(2) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `survey_question_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

ALTER TABLE `survey_question_choices`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `survey_question_choices`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

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
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
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
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
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

CREATE TABLE IF NOT EXISTS `institution_site_surveys` (
`id` int(11) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Draft, 2 -> Completed',
  `academic_period_id` int(11) NOT NULL,
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
  `answer_number` int(2) NOT NULL DEFAULT '1',
  `text_value` varchar(250) DEFAULT NULL,
  `int_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
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
  `value` varchar(250) DEFAULT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
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

--
-- 17. drop academic_period_types table and alter fields in survey_statuses
--

DROP TABLE IF EXISTS `academic_period_types`;

ALTER TABLE `survey_statuses` CHANGE `academic_period_type_id` `academic_period_level_id` INT(11) NOT NULL;

-- PHPOE-1145 (Areas)

--
-- 1. Backup
--

CREATE TABLE IF NOT EXISTS 1145_areas LIKE areas;
CREATE TABLE IF NOT EXISTS 1145_area_levels LIKE area_levels;
CREATE TABLE IF NOT EXISTS 1145_area_educations LIKE area_educations;
CREATE TABLE IF NOT EXISTS 1145_area_education_levels LIKE area_education_levels;

INSERT 1145_areas SELECT * FROM areas WHERE NOT EXISTS (SELECT * FROM 1145_areas);
INSERT 1145_area_levels SELECT * FROM area_levels WHERE NOT EXISTS (SELECT * FROM 1145_area_levels);
INSERT 1145_area_educations SELECT * FROM area_educations WHERE NOT EXISTS (SELECT * FROM 1145_area_educations);
INSERT 1145_area_education_levels SELECT * FROM area_education_levels WHERE NOT EXISTS (SELECT * FROM 1145_area_education_levels);

--
-- 2. Duplicate area_educations from areas, rename Areas Tables & Columns
--

DROP TABLE area_educations;
DROP TABLE area_education_levels;

CREATE TABLE IF NOT EXISTS area_administratives LIKE areas;
CREATE TABLE IF NOT EXISTS area_administrative_levels LIKE area_levels;

CREATE TABLE test_area SELECT * from areas;

INSERT area_administratives SELECT * FROM areas WHERE NOT EXISTS (SELECT * FROM area_administratives);
INSERT area_administrative_levels SELECT * FROM area_levels WHERE NOT EXISTS (SELECT * FROM area_administrative_levels);

ALTER TABLE `area_administratives` CHANGE `area_level_id` `area_administrative_level_id` INT(11) NOT NULL;
ALTER TABLE `area_administrative_levels` ADD `area_administrative_id` INT(11) NOT NULL AFTER `level`;

--
-- 3. Rename Columns in other tables
--

ALTER TABLE `institution_sites` CHANGE `area_education_id` `area_administrative_id` INT(11) NULL DEFAULT NULL;

--
-- 4. Update area_administrative_levels table
--

SET @idOfCurrentCountry := 0;
SELECT `id` INTO @idOfCurrentCountry FROM `area_administratives` WHERE `parent_id` = -1;

UPDATE `area_administrative_levels` SET `area_administrative_id` = @idOfCurrentCountry WHERE `level` <> 1;

UPDATE `area_administrative_levels` SET `level` = `level` - 1;
INSERT INTO `area_administrative_levels` (
`name`,
`level`,
`area_administrative_id`,
`created_user_id`,
`created`
) VALUES (
'World', '-1', 0, '1', '0000-00-00 00:00:00'
);

--
-- 5. Update area_administratives table
--

SET @levelIdOfWorld := 0;
SELECT `id` INTO @levelIdOfWorld FROM `area_administrative_levels` WHERE `level` = -1;

INSERT INTO `area_administratives` (
`code`,
`name`,
`parent_id`,
`area_administrative_level_id`,
`order`,
`visible`,
`created_user_id`,
`created`
) VALUES (
'World', 'World', '-1', @levelIdOfWorld, '1', '1', '1', '0000-00-00 00:00:00'
);

SET @parentIdOfWorld := 0;
SELECT `id` INTO @parentIdOfWorld FROM `area_administratives` WHERE `name` LIKE 'World' AND `parent_id` = -1;

UPDATE `area_administratives` SET `parent_id` = @parentIdOfWorld WHERE `parent_id` = '-1' AND `name` <> 'World';

UPDATE `area_administrative_levels` SET `area_administrative_id` = @parentIdOfWorld WHERE `level` = 0;

-- PHPOE-1156 (Update Footer)

UPDATE `config_items` 
SET 
  `value`='Copyright &copy; year OpenEMIS. All rights reserved.',
  `default_value`='Copyright &copy; 2015 OpenEMIS. All rights reserved.'
WHERE 
  `name`='footer'
AND
  `type`='System';


-- PHPOE-1159 (Added students permission)

UPDATE security_functions SET _view = REPLACE(_view , '|InstitutionSiteStudent.index','') WHERE name = 'Student' AND controller = 'Students' AND  module = 'Students';
UPDATE security_functions SET _add = REPLACE(_add , '|InstitutionSiteStudent.add','') WHERE name = 'Student' AND controller = 'Students' AND  module = 'Students';
-- SELECT * FROM security_functions WHERE name = 'Student' AND controller = 'Students' AND  module = 'Students';

UPDATE security_functions SET _view = REPLACE(_view , '|InstitutionSiteStaff.index','') WHERE name = 'Staff' AND controller = 'Staff' AND  module = 'Staff';
UPDATE security_functions SET _add = REPLACE(_add , '|InstitutionSiteStaff.add','') WHERE name = 'Staff' AND controller = 'Staff' AND  module = 'Staff';
-- SELECT * FROM security_functions WHERE name = 'Staff' AND controller = 'Staff' AND  module = 'Staff';

  
SET @lastDetailOrderNo := 0;
SELECT MAX(security_functions.order) INTO @lastDetailOrderNo FROM `security_functions` WHERE `category` = 'Details' AND controller = 'InstitutionSites' AND name <> 'Staff - Academic' AND name <> 'Students - Academic';
UPDATE security_functions SET security_functions.order = security_functions.order +2 WHERE security_functions.order > @lastDetailOrderNo;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_add`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (null , 'Student', 'Students', 'Institutions', 'Details', 8 , 'InstitutionSiteStudent|InstitutionSiteStudent.index', 'InstitutionSiteStudent.add', 'InstitutionSiteStudent.excel', @lastDetailOrderNo + 1 , 1, 1, NOW());

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_add`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (null , 'Staff', 'Staff', 'Institutions', 'Details', 8 , 'InstitutionSiteStaff|InstitutionSiteStaff.index', 'InstitutionSiteStaff.add', 'InstitutionSiteStaff.excel', @lastDetailOrderNo + 2 , 1, 1, NOW());

-- SELECT * FROM security_functions WHERE `category` = 'Details' AND name <> 'Staff - Academic' AND name <> 'Students - Academic';

-- PHPOE-1190 (Section & Classes)

ALTER TABLE `institution_site_sections` ADD `staff_id` INT NULL AFTER `name`, ADD INDEX (`staff_id`) ;
ALTER TABLE `institution_site_sections` ADD `education_grade_id` INT NULL AFTER `name`, ADD INDEX (`education_grade_id`) ;

SET @orderOfPositionsNav := 0;
SELECT `order` INTO @orderOfPositionsNav FROM `navigations` WHERE `module` LIKE 'Staff' AND `header` LIKE 'Details' AND `title` LIKE 'Positions';

UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` > @orderOfPositionsNav;

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
VALUES (NULL , 'Staff', 'Staff' , 'Staff', 'Details', 'Sections', 'StaffSection', 'StaffSection|StaffSection.index', NULL , '89', '0', @orderOfPositionsNav + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00');


SET @orderOfStudentProgrammesNav := 0;
SELECT `order` INTO @orderOfStudentProgrammesNav FROM `navigations` WHERE `module` LIKE 'Student' AND `header` LIKE 'Details' AND `title` LIKE 'Programmes';

UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` > @orderOfStudentProgrammesNav;

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
VALUES (NULL , 'Student', 'Students' , 'Students', 'Details', 'Sections', 'StudentSection', 'StudentSection|StudentSection.index', NULL , '62', '0', @orderOfStudentProgrammesNav + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00');

ALTER TABLE `institution_site_sections` ADD `section_number` INT NULL AFTER `name`;


SET @orderStudentDetailsProgsSecurity := 0;
SELECT `order` INTO @orderStudentDetailsProgsSecurity FROM `security_functions` WHERE `module` LIKE 'Students' AND `category` LIKE 'Details' AND `name` LIKE 'Programmes';

UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` > @orderStudentDetailsProgsSecurity;

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
NULL , 'Sections', 'Students', 'Students', 'Details', '66', 'StudentSection|StudentSection.index', NULL, NULL, NULL, NULL , @orderStudentDetailsProgsSecurity + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);


SET @orderStaffDetailsPositionsSecurity := 0;
SELECT `order` INTO @orderStaffDetailsPositionsSecurity FROM `security_functions` WHERE `module` LIKE 'Staff' AND `category` LIKE 'Details' AND `name` LIKE 'Positions';

UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` > @orderStaffDetailsPositionsSecurity;

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
NULL , 'Sections', 'Staff', 'Staff', 'Details', '84', 'StaffSection|StaffSection.index', NULL, NULL, NULL, NULL , @orderStaffDetailsPositionsSecurity + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

UPDATE `navigations` SET `action` = 'InstitutionSiteClass', `pattern` = 'InstitutionSiteClass' WHERE controller = 'InstitutionSites' AND header = 'Details' AND title = 'Classes';
ALTER TABLE `institution_site_classes` ADD `education_subject_id` INT NULL DEFAULT NULL AFTER `institution_site_id`;

CREATE TABLE IF NOT EXISTS 1190_config_items LIKE config_items;
INSERT 1190_config_items SELECT * FROM config_items WHERE name = 'max_subjects_per_class';
DELETE FROM config_items WHERE name = 'max_subjects_per_class';

UPDATE
      institution_site_classes t1
  INNER JOIN 
      ( SELECT institution_site_class_subjects.institution_site_class_id, MIN(education_grade_subject_id) AS education_grade_subject_id
        FROM institution_site_class_subjects 
        GROUP BY institution_site_class_subjects.institution_site_class_id
      ) AS t2
  ON t1.id = t2.institution_site_class_id 
  INNER JOIN (
      SELECT education_grades_subjects.id, education_grades_subjects.education_subject_id
      FROM education_grades_subjects
    ) AS t3
  ON t2.education_grade_subject_id = t3.id
SET t1.education_subject_id = t3.education_subject_id;

-- select * from institution_site_classes t1
--   INNER JOIN 
--       ( SELECT institution_site_class_subjects.institution_site_class_id, MIN(education_grade_subject_id) AS education_grade_subject_id
--         FROM institution_site_class_subjects 
--         GROUP BY institution_site_class_subjects.institution_site_class_id
--       ) AS t2
--   ON t1.id = t2.institution_site_class_id 
--   INNER JOIN (
--      SELECT education_grades_subjects.id, education_grades_subjects.education_subject_id
--      FROM education_grades_subjects
--    ) AS t3
--   ON t2.education_grade_subject_id = t3.id

RENAME TABLE institution_site_class_subjects to 1190_institution_site_class_subjects;

ALTER TABLE `institution_site_classes` DROP `institution_site_shift_id`;

UPDATE `security_functions` SET `_view` = 'InstitutionSiteSection|InstitutionSiteSection.index|InstitutionSiteSection.view' WHERE `controller` = 'InstitutionSites' AND `name` = 'Sections';
UPDATE `security_functions` SET `_view` = 'InstitutionSiteClass|InstitutionSiteClass.index|InstitutionSiteClass.view',
`_edit` = '_view:InstitutionSiteClass.edit',
`_add` = '_view:InstitutionSiteClass.add',
`_delete` = '_view:InstitutionSiteClass.delete'
WHERE `controller` = 'InstitutionSites' AND `name` = 'Classes';

-- PHPOE-1132

-- need to remove school year is 
CREATE TABLE IF NOT EXISTS 1132_field_options LIKE field_options;
INSERT 1132_field_options SELECT * FROM field_options WHERE field_options.code = "SchoolYear" AND NOT EXISTS (SELECT * FROM 1132_field_options WHERE 1132_field_options.code = "SchoolYear");
DELETE FROM field_options WHERE field_options.code = "SchoolYear";

CREATE TABLE IF NOT EXISTS 1132_school_years LIKE school_years;
INSERT 1132_school_years SELECT * FROM school_years WHERE NOT EXISTS (SELECT * FROM 1132_school_years);

CREATE TABLE `academic_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `start_year` int(4) NOT NULL,
  `end_date` date DEFAULT NULL,
  `end_year` int(4) DEFAULT NULL,
  `school_days` int(5) NOT NULL DEFAULT '0',
  `current` char(1) NOT NULL DEFAULT '0',
  `available` char(1) NOT NULL DEFAULT '1',
  `parent_id` int(11) NOT NULL,
  `lft` int(11) NOT NULL,
  `rght` int(11) NOT NULL,
  `academic_period_level_id` int(11) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `academic_periods` (`id`, `code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, `school_days`, `current`, `available`, `parent_id`, `lft`, `rght`, `academic_period_level_id`, `order`, `modified_user_id`, `modified`, `created_user_id`, `created` ) SELECT id , '', `name` , `start_date` , `start_year` , `end_date` , `end_year` , `school_days` , `current` , `available` , 1, 0, 0, 1, `order` , `modified_user_id` , `modified` , `created_user_id` , `created` FROM 1132_school_years;

INSERT INTO `academic_periods` (`id`, `code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, `school_days`, `current`, `available`, `parent_id`, `lft`, `rght`, `academic_period_level_id`, `order`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (null, 'All', 'All Data', '0000-00-00', '0000', NULL, NULL, 0, 0, '1', -1, 1, 2, -1, 1, NULL, NULL, 1, '0000-00-00 00:00:00');
DROP TABLE school_years;

-- need to update parent id
SELECT `id` INTO @academicPeriodAllDataId FROM `academic_periods` WHERE parent_id = -1;
UPDATE academic_periods SET parent_id = @academicPeriodAllDataId, academic_period_level_id = 1 WHERE parent_id != -1;

CREATE TABLE `academic_period_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `level` int(3) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- default values
SET @academicBoundriesOrderId := 0;
SELECT `order` INTO @academicBoundriesOrderId FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'System Setup' AND `title` LIKE 'Administrative Boundaries';
UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` > @academicBoundriesOrderId;
SELECT id INTO @academicBoundriesId FROM navigations WHERE header = 'System Setup' AND title = 'Administrative Boundaries'; 
INSERT INTO `navigations` (`module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Administration', NULL, 'AcademicPeriods', 'System Setup', 'Academic Periods', 'index', 'AcademicPeriod', NULL, @academicBoundriesId, 0, @academicBoundriesOrderId+1, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

INSERT INTO academic_period_levels (id, name, level, created_user_id, created) VALUES 
('1', 'Year', '1', 1, NOW());
-- ('2', 'Semester', '2', 1, NOW()),
-- ('3', 'Term', '3', 1, NOW()),
-- ('4', 'Month', '4', 1, NOW()),
-- ('5', 'Week', '5', 1, NOW());


-- select * from information_schema.columns where column_name = 'school_year_id'and table_schema = 'openemis-core';


ALTER TABLE `assessment_item_results` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `assessment_item_types` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `assessment_results` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_assessments` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_attendances` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_behaviours` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_buildings` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_classes` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_custom_values` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_energy` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_finances` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_furniture` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_graduates` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_grid_values` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_resources` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_rooms` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_sanitations` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_staff` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_students` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_teacher_fte` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_teacher_training` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_teachers` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_textbooks` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_verifications` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_water` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_classes` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_fees` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_programmes` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_sections` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_shifts` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `quality_institution_rubrics` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `quality_institution_visits` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `staff_attendances` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `staff_details_custom_values` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `staff_extracurriculars` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `student_attendances` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `student_details_custom_values` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `student_extracurriculars` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;



-- Academic period security SQL START
SET @lastAdminBoundaryOrderNo := 0;
SELECT MAX(security_functions.order) INTO @lastAdminBoundaryOrderNo FROM `security_functions` WHERE `category` = 'Administrative Boundaries' AND controller = 'Areas' AND name <> 'Staff - Academic' AND name <> 'Students - Academic';
UPDATE security_functions SET security_functions.order = security_functions.order +2 WHERE security_functions.order > @lastAdminBoundaryOrderNo;

INSERT INTO `security_functions` (`id`, 
  `name`, 
`controller`, 
`module`, 
`category`, 
`parent_id`, 
`_view`, 
`_edit`, 
`_add`, 
`order`, 
`visible`) VALUES
(null, 
  'Academic Period Levels', 
'AcademicPeriods', 
'Administration', 
'Academic Periods', 
-1, 
'AcademicPeriodLevel.index|AcademicPeriodLevel.view', 
'_view:AcademicPeriodLevel.edit', 
'_view:AcademicPeriodLevel.add', 
@lastAdminBoundaryOrderNo + 1, 
1
);

INSERT INTO `security_functions` (`id`, 
  `name`, 
`controller`, 
`module`, 
`category`, 
`parent_id`, 
`_view`, 
`_edit`, 
`_add`, 
`order`, 
`visible`) VALUES
(null, 
  'Academic Periods', 
'AcademicPeriods', 
'Administration', 
'Academic Periods', 
-1, 
'index|AcademicPeriod.index|AcademicPeriod.view', 
'_view:AcademicPeriod.edit|AcademicPeriod.reorder|AcademicPeriod.move', 
'_view:AcademicPeriod.add', 
@lastAdminBoundaryOrderNo + 2, 
1
);


-- need to set as parent
SELECT id INTO @parentId FROM `security_functions` WHERE name = 'Academic Periods' AND `category` = 'Academic Periods' AND controller = 'AcademicPeriods';

UPDATE security_functions SET parent_id = @parentId WHERE name = 'Academic Period Levels' AND `category` = 'Academic Periods' AND controller = 'AcademicPeriods';
-- Academic period security SQL END


-- PHPOE-1150 (Reports)

-- Drop unused columns and not show in reports
ALTER TABLE `student_behaviours` DROP `student_action_category_id` ;
ALTER TABLE `staff_behaviours` DROP `staff_action_category_id` ;

-- Move Institution -> Reports -> Dashboard to Institution -> Quality -> Dashboard
UPDATE `navigations` SET `header` = 'Quality' WHERE `module` = 'Institution' AND `controller` = 'Dashboards' AND `header` = 'Reports';

-- Remove all links under Institution -> Reports, Staff -> Reports
DELETE FROM `navigations` WHERE `module` = 'Institution' AND `header` = 'Reports';
DELETE FROM `navigations` WHERE `controller` = 'Staff' AND `header` = 'Reports';
DELETE FROM `security_functions` WHERE `category` = 'Reports' AND `controller` <> 'Dashboards';

-- Update security_functions to enable execute permissions to be configurable
UPDATE `security_functions` SET `_execute` = '_view:excel' WHERE `name` = 'Institution' AND `controller` = 'InstitutionSites' AND `category` = 'General';
UPDATE `security_functions` SET `_execute` = '_view:qualityVisitExcel' WHERE `name` = 'Visits' AND `controller` = 'Quality' AND `module` = 'Institutions';
UPDATE `security_functions` SET `_execute` = '_view:StudentBehaviour.excel' WHERE `name` = 'Students' AND `controller` = 'InstitutionSites' AND `category` = 'Behaviour';
UPDATE `security_functions` SET `_execute` = '_view:StaffBehaviour.excel' WHERE `name` = 'Staff' AND `controller` = 'InstitutionSites' AND `category` = 'Behaviour';
UPDATE `security_functions` SET `category` = 'Quality' WHERE `controller` = 'Dashboards' AND `name` = 'Dashboards' AND `module` = 'Institutions';

UPDATE `navigations` SET `action` = 'Absence', `pattern` = 'Absence' WHERE `plugin` = 'Students' AND `controller` = 'Students' AND `title` = 'Absence';


-- PHPOE-954 (Reports)

DROP TABLE IF EXISTS `report_progress`;

CREATE TABLE IF NOT EXISTS `report_progress` (
  `id` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `module` varchar(100) NULL,
  `params` text NOT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `current_records` int(11) NOT NULL DEFAULT '0',
  `total_records` int(11) NOT NULL DEFAULT '0',
  `pid` int(11) NOT NULL DEFAULT '0',
  `status` int(1) NOT NULL DEFAULT '1',
  `error_message` text NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE `navigations` SET `plugin` = NULL, `controller` = 'InstitutionReports', `title` = 'List of Reports', `action` = 'index', `pattern` = 'index' WHERE `parent` = -1 AND `controller` = 'Reports' AND `title` = 'General' AND `action` = 'InstitutionGeneral';
UPDATE `navigations` SET `plugin` = NULL, `controller` = 'InstitutionReports', `title` = 'Generate', `action` = 'generate', `pattern` = 'generate' WHERE `controller` = 'Reports' AND `title` = 'Details' AND `action` = 'InstitutionDetails';

UPDATE `navigations` SET `plugin` = 'Students', `controller` = 'StudentReports', `title` = 'List of Reports', `action` = 'index', `pattern` = 'index' WHERE `controller` = 'Reports' AND `title` = 'General' AND `action` = 'StudentGeneral';
UPDATE `navigations` SET `plugin` = 'Students', `controller` = 'StudentReports', `title` = 'Generate', `action` = 'generate', `pattern` = 'generate' WHERE `controller` = 'Reports' AND `title` = 'Details' AND `action` = 'StudentDetails';

UPDATE `navigations` SET `plugin` = 'Staff', `controller` = 'StaffReports', `title` = 'List of Reports', `action` = 'index', `pattern` = 'index' WHERE `controller` = 'Reports' AND `title` = 'General' AND `action` = 'StaffGeneral';
UPDATE `navigations` SET `plugin` = 'Staff', `controller` = 'StaffReports', `title` = 'Generate', `action` = 'generate', `pattern` = 'generate' WHERE `controller` = 'Reports' AND `title` = 'Details' AND `action` = 'StaffDetails';

-- hide other report links
UPDATE `navigations` SET `visible` = 0 WHERE `controller` = 'Reports' 
AND `action` IN (
  'InstitutionAttendance', 'InstitutionAssessment', 'InstitutionBehaviors', 'InstitutionFinance', 'InstitutionTotals', 'InstitutionQuality',
  'StudentFinance', 'StudentHealth',
  'StaffFinance', 'StaffHealth', 'StaffTraining'
);









