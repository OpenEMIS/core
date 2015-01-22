--
-- 1. navigations
--

SET @orderEduStructure := 0;
SELECT `order` INTO @orderEduStructure FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'System Setup' AND `title` LIKE 'Education Structure';

DELETE FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'System Setup' AND `title` LIKE 'Infrastructure';

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @orderEduStructure;


SET @orderDetailsClasses := 0;
SELECT `order` INTO @orderDetailsClasses FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Details' AND `title` LIKE 'Classes';

DELETE FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Details' AND `title` LIKE 'Infrastructure';

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @orderDetailsClasses;

ALTER TABLE `navigations` CHANGE `pattern` `pattern` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

--
-- 2. security_functions
--

SET @orderDetailsClassesSecurity := 0;
SELECT `order` INTO @orderDetailsClassesSecurity FROM `security_functions` WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Details' AND `name` LIKE 'Classes';

DELETE FROM `security_functions` WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Details' AND `name` LIKE 'Infrastructure';

UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` > @orderDetailsClassesSecurity;


SET @orderEduProgSecurity := 0;
SELECT `order` INTO @orderEduProgSecurity FROM `security_functions` WHERE `module` LIKE 'Administration' AND `category` LIKE 'Education' AND `name` LIKE 'Education Programme Orientations';

DELETE FROM `security_functions` WHERE `module` LIKE 'Administration' AND `category` LIKE 'Infrastructure' AND `name` LIKE 'Categories';
DELETE FROM `security_functions` WHERE `module` LIKE 'Administration' AND `category` LIKE 'Infrastructure' AND `name` LIKE 'Types';
DELETE FROM `security_functions` WHERE `module` LIKE 'Administration' AND `category` LIKE 'Infrastructure' AND `name` LIKE 'Custom Fields';

UPDATE `security_functions` SET `order` = `order` - 3 WHERE `order` > @orderEduProgSecurity;

--
-- 3. infrastructure_categories
--

ALTER TABLE `infrastructure_categories` DROP `parent_id` ;

--
-- 4. `infrastructure_types`
--

DROP TABLE IF EXISTS `infrastructure_types`;

--
-- 5. Table structure for table `institution_site_infrastructures`
--

DROP TABLE IF EXISTS `institution_site_infrastructures`;

--
-- 6. new field option `InfrastructureOwnership`
--

DELETE FROM `field_options` WHERE `code` LIKE 'InfrastructureOwnership';

--
-- 7. new field option `InfrastructureCondition`
--

DELETE FROM `field_options` WHERE `code` LIKE 'InfrastructureCondition';

--
-- 8. Table structure for table `infrastructure_custom_fields`
--

DROP TABLE IF EXISTS `infrastructure_custom_fields`;

--
-- 9. Table structure for table `infrastructure_custom_field_options`
--

DROP TABLE IF EXISTS `infrastructure_custom_field_options`;

--
-- 10. Table structure for table `institution_site_infrastructure_custom_values`
--

DROP TABLE IF EXISTS `institution_site_infrastructure_custom_values`;