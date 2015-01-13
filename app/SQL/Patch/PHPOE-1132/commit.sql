CREATE TABLE `academic_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `lft` int(11) DEFAULT NULL,
  `rght` int(11) DEFAULT NULL,
  `academic_period_level_id` int(11) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_period_level_id` (`academic_period_level_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `academic_period_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `level` int(3) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- default values
SET @academicBoundriesOrderId := 0;
SELECT `order` INTO @academicBoundriesOrderId FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'System Setup' AND `title` LIKE 'Administrative Boundaries';
UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` > @academicBoundriesOrderId;
SELECT id INTO @academicBoundriesId FROM navigations WHERE header = 'System Setup' AND title = 'Administrative Boundaries'; 
INSERT INTO `navigations` (`module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Administration', NULL, 'AcademicPeriods', 'System Setup', 'Academic Periods', 'index', 'AcademicPeriod', NULL, @academicBoundriesId, 0, @academicBoundriesOrderId+1, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

INSERT INTO academic_period_levels (id, name, level) VALUES 
('1', 'Year', '1'),
('2', 'Semester', '2'),
('3', 'Term', '3'),
('4', 'Month', '4'),
('5', 'Week', '5');


-- select * from information_schema.columns where column_name = 'school_year_id'and table_schema = 'openemis-core';


ALTER TABLE `assessment_item_results` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `assessment_item_types` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `assessment_results` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_assessments` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_attendances` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_behaviours` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_buildings` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_classes` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_custom_values` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_energy` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_finances` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_furniture` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_graduates` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_grid_values` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_resources` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_rooms` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_sanitations` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_staff` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_students` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_teacher_fte` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_teacher_training` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_teachers` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_textbooks` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_verifications` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `census_water` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_classes` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_fees` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_programmes` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_sections` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_shifts` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `quality_institution_rubrics` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `quality_institution_visits` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `staff_attendances` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `staff_details_custom_values` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `staff_extracurriculars` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `student_attendances` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `student_details_custom_values` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;
ALTER TABLE `student_extracurriculars` CHANGE `school_year_id` `academic_period_id` INT(11) NOT NULL;

