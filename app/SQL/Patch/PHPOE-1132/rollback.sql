DROP TABLE academic_periods;
DROP TABLE academic_period_levels;


SET @academicPeriodOrderId := 0;
SELECT `order` INTO @academicPeriodOrderId FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'System Setup' AND `title` LIKE 'Academic Periods';
DELETE FROM navigations WHERE `module` LIKE 'Administration' AND `header` LIKE 'System Setup' AND `title` LIKE 'Academic Periods' LIMIT 1;

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @academicPeriodOrderId;

ALTER TABLE `assessment_item_results` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `assessment_item_types` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `assessment_results` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_assessments` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_attendances` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_behaviours` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_buildings` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_classes` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_custom_values` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_energy` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_finances` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_furniture` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_graduates` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_grid_values` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_resources` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_rooms` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_sanitations` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_staff` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_students` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_teacher_fte` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_teacher_training` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_teachers` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_textbooks` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_verifications` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `census_water` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_classes` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_fees` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_programmes` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_sections` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_shifts` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `quality_institution_rubrics` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `quality_institution_visits` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `staff_attendances` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `staff_details_custom_values` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `staff_extracurriculars` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `student_attendances` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `student_details_custom_values` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;
ALTER TABLE `student_extracurriculars` CHANGE `academic_period_id` `school_year_id` INT(11) NOT NULL;


INSERT field_options SELECT * FROM 1132_field_options WHERE 1132_field_options.code = "SchoolYear" AND NOT EXISTS (SELECT * FROM field_options WHERE field_options.code = "SchoolYear");


DROP TABLE academic_period_levels;

DROP TABLE academic_periods;
RENAME TABLE 1132_school_years to school_years;

