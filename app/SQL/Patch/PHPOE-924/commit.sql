--
-- 1. navigations
--

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
VALUES (NULL , 'Administration', 'Infrastructure' , 'InfrastructureCategories', 'System Setup', 'Infrastructure', 'index', 'index|view|add|edit|delete', NULL , '33', '0', @orderEduStructure + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00');

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
VALUES (NULL , 'Institution', NULL , 'InstitutionSites', 'Infrastructure', 'Infrastructure', 'InstitutionSiteInfrastructure', 'InstitutionSiteInfrastructure|InstitutionSiteInfrastructure.index|InstitutionSiteInfrastructure.view|InstitutionSiteInfrastructure.add|InstitutionSiteInfrastructure.edit|InstitutionSiteInfrastructure.delete', NULL , '3', '0', @orderDetailsClasses + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00');

--
-- 2. infrastructure_categories
--

ALTER TABLE `infrastructure_categories` ADD `parent_id` INT NOT NULL AFTER `national_code` ;

--
-- 3. Table structure for table `infrastructure_types`
--

CREATE TABLE `infrastructure_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `order` int(3) NOT NULL,
  `infrastructure_category_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `infrastructure_category_id` (`infrastructure_category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


--
-- 4. Table structure for table `institution_site_infrastructures`
--

CREATE TABLE `institution_site_infrastructures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(50) NOT NULL,
  `year_acquired` year(4) DEFAULT NULL,
  `year_disposed` year(4) DEFAULT NULL,
  `infrastructure_category_id` int(11) NOT NULL,
  `infrastructure_type_id` int(11) NOT NULL,
  `infrastructure_ownership_id` int(11) NOT NULL,
  `modified_user_id` int(5) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(5) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `code` (`code`),
  KEY `infrastructure_category_id` (`infrastructure_category_id`),
  KEY `infrastructure_type_id` (`infrastructure_type_id`),
  KEY `infrastructure_ownership_id` (`infrastructure_ownership_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
