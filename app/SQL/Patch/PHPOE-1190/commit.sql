ALTER TABLE `institution_site_sections` ADD `institution_site_staff_id` INT NULL AFTER `name`, ADD INDEX (`institution_site_staff_id`) ;
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

