--
-- 1. `institution_site_sections`
--
TRUNCATE TABLE `institution_site_sections`;

INSERT INTO `institution_site_sections` (`id`, `name`, `institution_site_shift_id`, `institution_site_id`, `school_year_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT `id`, `name`, `institution_site_shift_id`, `institution_site_id`, `school_year_id`, `modified_user_id`, `modified`, `created_user_id`, `created` 
FROM `institution_site_classes` ;

--
-- 2. `institution_site_section_classes`
--
TRUNCATE TABLE `institution_site_section_classes`;

INSERT INTO `institution_site_section_classes` (`id`, `status`, `institution_site_section_id`, `institution_site_class_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT NULL, 1, `id`, `id`, NULL, NULL, 1, '00-00-00 00:00:00' 
FROM `institution_site_classes` ;

--
-- 3. `institution_site_section_grades`
--
TRUNCATE TABLE `institution_site_section_grades`;

INSERT INTO `institution_site_section_grades` (`id`, `status`, `institution_site_section_id`, `education_grade_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT `id`, `status`, `institution_site_class_id`, `education_grade_id`, `modified_user_id`, `modified`, `created_user_id`, `created` 
FROM `institution_site_class_grades` ;

DROP TABLE IF EXISTS `institution_site_class_grades`;

--
-- 4. `institution_site_section_students`
--
TRUNCATE TABLE `institution_site_section_students`;

INSERT INTO `institution_site_section_students` (`id`, `student_id`, `institution_site_section_id`, `education_grade_id`, `student_category_id`, `status`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT `id`, `student_id`, `institution_site_class_id`, `education_grade_id`, `student_category_id`, `status`, `modified_user_id`, `modified`, `created_user_id`, `created` 
FROM `institution_site_class_students` ;

--
-- 5. `institution_site_section_staff`
--
TRUNCATE TABLE `institution_site_section_staff`;

INSERT INTO `institution_site_section_staff` (`id`, `status`, `staff_id`, `institution_site_section_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT `id`, `status`, `staff_id`, `institution_site_class_id`, `modified_user_id`, `modified`, `created_user_id`, `created` 
FROM `institution_site_class_staff` ;

--
-- 6. `institution_site_class_students` 
--

UPDATE `institution_site_class_students` SET `institution_site_section_id` = `institution_site_class_id`;

--
-- 7. DROP `institution_site_shift_id` FROM `institution_site_classes`
--

ALTER TABLE `institution_site_classes` DROP `institution_site_shift_id` ;

--
-- 8. DROP `student_category_id`, `education_grade_id` FROM `institution_site_class_students`
--

ALTER TABLE `institution_site_class_students` DROP `student_category_id` ;
ALTER TABLE `institution_site_class_students` DROP `education_grade_id` ;

--
-- 9. ADD INDEX TO  `institution_site_student_absences` FROM `institution_site_student_absences`
--

ALTER TABLE `institution_site_student_absences` ADD INDEX ( `institution_site_section_id` ) ;

