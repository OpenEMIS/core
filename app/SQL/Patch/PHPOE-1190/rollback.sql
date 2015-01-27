ALTER TABLE `institution_site_sections` DROP `institution_site_staff_id`;
ALTER TABLE `institution_site_sections` DROP `education_grade_id`;

SET @orderOfPositionsNav := 0;
SELECT `order` INTO @orderOfPositionsNav FROM `navigations` WHERE `module` LIKE 'Staff' AND `header` LIKE 'Details' AND `title` LIKE 'Positions';

DELETE FROM `navigations` WHERE `module` LIKE 'Staff' AND `header` LIKE 'Details' AND `title` LIKE 'Sections';

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @orderOfPositionsNav;

SET @orderOfStudentProgrammesNav := 0;
SELECT `order` INTO @orderOfStudentProgrammesNav FROM `navigations` WHERE `module` LIKE 'Student' AND `header` LIKE 'Details' AND `title` LIKE 'Programmes';

DELETE FROM `navigations` WHERE `module` LIKE 'Student' AND `header` LIKE 'Details' AND `title` LIKE 'Sections';

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @orderOfStudentProgrammesNav;