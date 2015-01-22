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
VALUES (NULL , 'Administration', 'Infrastructure' , 'InfrastructureCategories|InfrastructureTypes|InfrastructureCustomFields', 'System Setup', 'Infrastructure', 'index', 'index|view|add|edit|delete|remove|categories|reorder|preview', NULL , '33', '0', @orderEduStructure + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00');

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

INSERT INTO `_openemis_`.`security_functions` (
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

INSERT INTO `_openemis_`.`security_functions` (
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
(NULL , 'Categories', 'InfrastructureCategories', 'Administration', 'Infrastructure', '-1', 'index|view', '_view:edit', '_view:add', '_view:remove', NULL , @orderEduProgSecurity + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00');

SET @securityInfraCatId := 0;
SELECT `id` INTO @securityInfraCatId FROM `security_functions` WHERE `module` LIKE 'Administration' AND `category` LIKE 'Infrastructure' AND `name` LIKE 'Categories';

INSERT INTO `_openemis_`.`security_functions` (
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
-- 3. infrastructure_categories
--

ALTER TABLE `infrastructure_categories` ADD `parent_id` INT NOT NULL AFTER `national_code` ;

--
-- 4. Table structure for table `infrastructure_types`
--

CREATE TABLE `infrastructure_types` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `order` int(3) NOT NULL,
  `infrastructure_category_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Indexes for table `infrastructure_types`
--
ALTER TABLE `infrastructure_types`
 ADD PRIMARY KEY (`id`), ADD KEY `infrastructure_category_id` (`infrastructure_category_id`);


--
-- AUTO_INCREMENT for table `infrastructure_types`
--
ALTER TABLE `infrastructure_types`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;


--
-- 5. Table structure for table `institution_site_infrastructures`
--


CREATE TABLE `institution_site_infrastructures` (
`id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `code` varchar(50) NOT NULL,
  `year_acquired` year(4) DEFAULT NULL,
  `year_disposed` year(4) DEFAULT NULL,
  `comment` text NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `institution_site_id` int(11) NOT NULL,
  `infrastructure_category_id` int(11) NOT NULL,
  `infrastructure_type_id` int(11) NOT NULL,
  `infrastructure_ownership_id` int(11) NOT NULL,
  `infrastructure_condition_id` int(11) NOT NULL,
  `modified_user_id` int(5) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(5) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


--
-- Indexes for table `institution_site_infrastructures`
--
ALTER TABLE `institution_site_infrastructures`
 ADD PRIMARY KEY (`id`), ADD KEY `name` (`name`), ADD KEY `code` (`code`), ADD KEY `infrastructure_category_id` (`infrastructure_category_id`), ADD KEY `infrastructure_type_id` (`infrastructure_type_id`), ADD KEY `infrastructure_ownership_id` (`infrastructure_ownership_id`), ADD KEY `institution_site_id` (`institution_site_id`), ADD KEY `parent_id` (`parent_id`), ADD KEY `infrastructure_condition_id` (`infrastructure_condition_id`);


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
  `infrastructure_category_id` int(11) NOT NULL DEFAULT '0',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


--
-- Indexes for table `infrastructure_custom_fields`
--
ALTER TABLE `infrastructure_custom_fields`
 ADD PRIMARY KEY (`id`), ADD KEY `infrastructure_category_id` (`infrastructure_category_id`);


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
