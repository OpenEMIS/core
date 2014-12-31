--
-- 1. navigations
--
SET @orderEduStructure := 0;
SELECT `order` INTO @orderEduStructure FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'System Setup' AND `title` LIKE 'Education Structure';

DELETE FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'System Setup' AND `title` LIKE 'Infrastructure';

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @orderEduStructure;

--
-- 2. infrastructure_categories
--

ALTER TABLE `infrastructure_categories` DROP `parent_id` ;

--
-- 3. `infrastructure_types`
--

DROP TABLE IF EXISTS `infrastructure_types`;