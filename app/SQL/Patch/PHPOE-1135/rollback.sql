--
-- 1. navigations
--
SET @orderOfPositionsNav := 0;
SELECT `order` INTO @orderOfPositionsNav FROM `navigations` WHERE `module` LIKE 'Staff' AND `header` LIKE 'Details' AND `title` LIKE 'Positions';

DELETE FROM `navigations` WHERE `module` LIKE 'Staff' AND `header` LIKE 'Details' AND `title` LIKE 'Classes';

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @orderOfPositionsNav;
