--
-- 1. navigations
--

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
VALUES (NULL , 'Staff', 'Staff' , 'Staff', 'Details', 'Classes', 'StaffClass', 'StaffClass|StaffClass.index', NULL , '89', '0', @orderOfPositionsNav + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00');