ALTER TABLE `institution_site_sections` DROP `staff_id`;
ALTER TABLE `institution_site_sections` DROP `education_grade_id`;

SET @orderOfPositionsNav := 0;
SELECT `order` INTO @orderOfPositionsNav FROM `navigations` WHERE `module` LIKE 'Staff' AND `header` LIKE 'Details' AND `title` LIKE 'Positions';

DELETE FROM `navigations` WHERE `module` LIKE 'Staff' AND `header` LIKE 'Details' AND `title` LIKE 'Sections';

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @orderOfPositionsNav;

SET @orderOfStudentProgrammesNav := 0;
SELECT `order` INTO @orderOfStudentProgrammesNav FROM `navigations` WHERE `module` LIKE 'Student' AND `header` LIKE 'Details' AND `title` LIKE 'Programmes';

DELETE FROM `navigations` WHERE `module` LIKE 'Student' AND `header` LIKE 'Details' AND `title` LIKE 'Sections';

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @orderOfStudentProgrammesNav;

ALTER TABLE `institution_site_sections` DROP `section_number`;

--
--  security_functions for Student/Staff Sections
--

SET @orderStudentDetailsProgsSecurity := 0;
SELECT `order` INTO @orderStudentDetailsProgsSecurity FROM `security_functions` WHERE `module` LIKE 'Students' AND `category` LIKE 'Details' AND `name` LIKE 'Programmes';

DELETE FROM `security_functions` WHERE `module` LIKE 'Students' AND `category` LIKE 'Details' AND `name` LIKE 'Sections'; 

UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` > @orderStudentDetailsProgsSecurity;


SET @orderStaffDetailsPositionsSecurity := 0;
SELECT `order` INTO @orderStaffDetailsPositionsSecurity FROM `security_functions` WHERE `module` LIKE 'Staff' AND `category` LIKE 'Details' AND `name` LIKE 'Positions';

DELETE FROM `security_functions` WHERE `module` LIKE 'Staff' AND `category` LIKE 'Details' AND `name` LIKE 'Sections'; 

UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` > @orderStaffDetailsPositionsSecurity;

-- Malcolm SQL START 
UPDATE `navigations` SET `action` = 'classes', `pattern` = 'classes' WHERE controller = 'InstitutionSites' AND header = 'Details' AND title = 'Classes';

ALTER TABLE `institution_site_classes` DROP `education_grade_subject_id`;

INSERT config_items SELECT * FROM 1190_config_items WHERE name = 'max_subjects_per_class';

RENAME TABLE 1190_institution_site_class_subjects to institution_site_class_subjects;
-- Malcolm SQL END


-- Academic period security SQL START
SET @lastAcademicPeriodNo := 0;
SELECT MAX(security_functions.order) INTO @lastAcademicPeriodNo FROM `security_functions` WHERE `category` = 'Academic Periods' AND controller = 'AcademicPeriods' AND name <> 'Staff - Academic' AND name <> 'Students - Academic';
UPDATE security_functions SET security_functions.order = security_functions.order -2 WHERE security_functions.order > @lastAcademicPeriodNo;

DELETE FROM security_functions WHERE name = 'Academic Period Levels'  AND `category` = 'Academic Periods' AND controller = 'AcademicPeriods';
DELETE FROM security_functions WHERE name = 'Academic Periods' AND `category` = 'Academic Periods' AND controller = 'AcademicPeriods';
-- SELECT name, security_functions.order FROM security_functions WHERE security_functions.order > 130 order by security_functions.order ASC;

-- Academic period security SQL END