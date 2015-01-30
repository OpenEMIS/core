ALTER TABLE `institution_site_sections` ADD `staff_id` INT NULL AFTER `name`, ADD INDEX (`staff_id`) ;
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

ALTER TABLE `institution_site_sections` ADD `section_number` INT NULL AFTER `name`;

--
--  security_functions for Student/Staff Sections
--

SET @orderStudentDetailsProgsSecurity := 0;
SELECT `order` INTO @orderStudentDetailsProgsSecurity FROM `security_functions` WHERE `module` LIKE 'Students' AND `category` LIKE 'Details' AND `name` LIKE 'Programmes';

UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` > @orderStudentDetailsProgsSecurity;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_view` ,
`_edit` ,
`_add` ,
`_delete` ,
`_execute` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Sections', 'Students', 'Students', 'Details', '66', 'StudentSection|StudentSection.index', NULL, NULL, NULL, NULL , @orderStudentDetailsProgsSecurity + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);


SET @orderStaffDetailsPositionsSecurity := 0;
SELECT `order` INTO @orderStaffDetailsPositionsSecurity FROM `security_functions` WHERE `module` LIKE 'Staff' AND `category` LIKE 'Details' AND `name` LIKE 'Positions';

UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` > @orderStaffDetailsPositionsSecurity;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_view` ,
`_edit` ,
`_add` ,
`_delete` ,
`_execute` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Sections', 'Staff', 'Staff', 'Details', '84', 'StaffSection|StaffSection.index', NULL, NULL, NULL, NULL , @orderStaffDetailsPositionsSecurity + 1, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

-- Malcolm SQL START 
UPDATE `navigations` SET `action` = 'InstitutionSiteClass', `pattern` = 'InstitutionSiteClass' WHERE controller = 'InstitutionSites' AND header = 'Details' AND title = 'Classes';
ALTER TABLE `institution_site_classes` ADD `education_subject_id` INT NULL DEFAULT NULL AFTER `institution_site_id`;

CREATE TABLE IF NOT EXISTS 1190_config_items LIKE config_items;
INSERT 1190_config_items SELECT * FROM config_items WHERE name = 'max_subjects_per_class';
DELETE FROM config_items WHERE name = 'max_subjects_per_class';


UPDATE
      institution_site_classes t1
  INNER JOIN 
      ( SELECT institution_site_class_subjects.institution_site_class_id, MIN(education_grade_subject_id) AS education_grade_subject_id
        FROM institution_site_class_subjects 
        GROUP BY institution_site_class_subjects.institution_site_class_id
      ) AS t2
  ON t1.id = t2.institution_site_class_id 
  INNER JOIN (
  		SELECT education_grades_subjects.id, education_grades_subjects.education_subject_id
  		FROM education_grades_subjects
  	) AS t3
  ON t2.education_grade_subject_id = t3.id
SET t1.education_subject_id = t3.education_subject_id;

-- select * from institution_site_classes t1
--   INNER JOIN 
--       ( SELECT institution_site_class_subjects.institution_site_class_id, MIN(education_grade_subject_id) AS education_grade_subject_id
--         FROM institution_site_class_subjects 
--         GROUP BY institution_site_class_subjects.institution_site_class_id
--       ) AS t2
--   ON t1.id = t2.institution_site_class_id 
--   INNER JOIN (
--   		SELECT education_grades_subjects.id, education_grades_subjects.education_subject_id
--   		FROM education_grades_subjects
--   	) AS t3
--   ON t2.education_grade_subject_id = t3.id

RENAME TABLE institution_site_class_subjects to 1190_institution_site_class_subjects;

ALTER TABLE `institution_site_classes` DROP `institution_site_shift_id`;

UPDATE `security_functions` SET `_view` = 'InstitutionSiteSection|InstitutionSiteSection.index|InstitutionSiteSection.view' WHERE `controller` = 'InstitutionSites' AND `name` = 'Sections';
UPDATE `security_functions` SET `_view` = 'InstitutionSiteClass|InstitutionSiteClass.index|InstitutionSiteClass.view',
`_edit` = '_view:InstitutionSiteClass.edit',
`_add` = '_view:InstitutionSiteClass.add',
`_delete` = '_view:InstitutionSiteClass.delete'
WHERE `controller` = 'InstitutionSites' AND `name` = 'Classes';

-- Malcolm SQL END
