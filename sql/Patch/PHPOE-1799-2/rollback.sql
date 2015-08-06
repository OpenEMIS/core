--
-- PHPOE-1799-2 rollback.sql
--

ALTER TABLE `institution_site_section_students` ADD `student_category_id` INT(11) NOT NULL AFTER `education_grade_id`;
ALTER TABLE `institution_site_section_students` CHANGE `student_id` `security_user_id` INT(11) NOT NULL;

-- Latest
ALTER TABLE `institution_site_class_students` CHANGE `student_id` `security_user_id` INT(11) NOT NULL;
