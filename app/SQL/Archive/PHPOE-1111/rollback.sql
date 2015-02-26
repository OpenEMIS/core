--
-- 1. navigations
--

DELETE FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Details' AND `title` LIKE 'Sections';

SET @orderOfClasses := 0;
SELECT `order` INTO @orderOfClasses FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Details' AND `title` LIKE 'Classes';

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` >= @orderOfClasses;

--
-- 2. security_functions
--

DELETE FROM `security_functions` WHERE `controller` LIKE 'InstitutionSites' AND `category` LIKE 'Details' AND `name` LIKE 'Sections';

SET @orderOfClassesSecurity := 0;
SELECT `order` INTO @orderOfClassesSecurity FROM `security_functions` WHERE `controller` LIKE 'InstitutionSites' AND `category` LIKE 'Details' AND `name` LIKE 'Classes';

UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` >= @orderOfClassesSecurity;

--
-- 3. set institution_site_shift_id to NULL (institution_site_classes)
--
ALTER TABLE `institution_site_classes` CHANGE `institution_site_shift_id` `institution_site_shift_id` INT( 11 ) NOT NULL ;

--
-- 4. set student_category_id to NULL (institution_site_class_students)
--

ALTER TABLE `institution_site_class_students` CHANGE `student_category_id` `student_category_id` INT( 11 ) NOT NULL ;

--
-- 5. set education_grade_id to NULL (institution_site_class_students)
--

ALTER TABLE `institution_site_class_students` CHANGE `education_grade_id` `education_grade_id` INT( 11 ) NOT NULL ;

--
-- 6. add new column `institution_site_section_id` to table `institution_site_class_students`
--

ALTER TABLE `institution_site_class_students` DROP `institution_site_section_id` ;

--
-- 7. add new config item
--

DELETE FROM `config_items` WHERE `name` LIKE 'max_subjects_per_class';

--
-- 8. changes to `institution_site_student_absences`
--

ALTER TABLE `institution_site_student_absences` ADD `institution_site_class_id` INT NOT NULL AFTER `student_id` ;
ALTER TABLE `institution_site_student_absences` DROP `institution_site_section_id` ;

--
-- 9. new tables
--

DROP TABLE IF EXISTS `institution_site_sections`;

DROP TABLE IF EXISTS `institution_site_section_classes`;

DROP TABLE IF EXISTS `institution_site_section_grades`;

DROP TABLE IF EXISTS `institution_site_section_staff`;

DROP TABLE IF EXISTS `institution_site_section_students`;