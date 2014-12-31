--
-- 1. navigations
--

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

