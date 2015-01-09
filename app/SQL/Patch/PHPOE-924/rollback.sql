--
-- 1. navigations
--

SET @orderEduStructure := 0;
SELECT `order` INTO @orderEduStructure FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'System Setup' AND `title` LIKE 'Education Structure';

DELETE FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'System Setup' AND `title` LIKE 'Infrastructure';

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @orderEduStructure;


SET @orderDetailsClasses := 0;
SELECT `order` INTO @orderDetailsClasses FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Details' AND `title` LIKE 'Classes';

DELETE FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Infrastructure' AND `title` LIKE 'Infrastructure';

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @orderDetailsClasses;

ALTER TABLE `navigations` CHANGE `pattern` `pattern` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

--
-- 2. infrastructure_categories
--

ALTER TABLE `infrastructure_categories` DROP `parent_id` ;

--
-- 3. `infrastructure_types`
--

DROP TABLE IF EXISTS `infrastructure_types`;

--
-- 4. Table structure for table `institution_site_infrastructures`
--

DROP TABLE IF EXISTS `institution_site_infrastructures`;

--
-- 5. new field option `InfrastructureOwnership`
--

DELETE FROM `field_options` WHERE `code` LIKE 'InfrastructureOwnership';

--
-- 6. new field option `InfrastructureCondition`
--

DELETE FROM `field_options` WHERE `code` LIKE 'InfrastructureCondition';