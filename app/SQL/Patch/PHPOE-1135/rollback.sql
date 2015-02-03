--
-- 1. navigations
--
SET @orderOfPositionsNav := 0;
SELECT `order` INTO @orderOfPositionsNav FROM `navigations` WHERE `module` LIKE 'Staff' AND `header` LIKE 'Details' AND `title` LIKE 'Positions';

DELETE FROM `navigations` WHERE `module` LIKE 'Staff' AND `header` LIKE 'Details' AND `title` LIKE 'Classes';

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @orderOfPositionsNav;

--
-- 2. security_functions
--

SET @orderStaffPositionsSecurity := 0;
SELECT `order` INTO @orderStaffPositionsSecurity FROM `security_functions` WHERE `module` LIKE 'Staff' AND `category` LIKE 'Details' AND `name` LIKE 'Positions';

DELETE FROM `security_functions` WHERE `module` LIKE 'Staff' AND `category` LIKE 'Details' AND `name` LIKE 'Classes'; 

UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` > @orderStaffPositionsSecurity;
