--
-- PHPOE-1799-2 rollback.sql
--

ALTER TABLE `institution_site_section_students` ADD `student_category_id` INT(11) NOT NULL AFTER `education_grade_id`;
ALTER TABLE `institution_site_section_students` CHANGE `student_id` `security_user_id` INT(11) NOT NULL;

-- Latest
ALTER TABLE `institution_site_class_students` CHANGE `student_id` `security_user_id` INT(11) NOT NULL;

-- rollback permissions for Grades
DELETE FROM `security_functions` WHERE `id` = 1005;

UPDATE `security_functions` SET
`id` = `id` - 1,
`order` = `order` - 1
WHERE `id` > 1004 AND `id` < 2000;

UPDATE `security_role_functions` SET
`security_function_id` = `security_function_id` - 1
WHERE `security_function_id` > 1004 AND `security_function_id` < 2000;

-- rollback permission for my subjects
DELETE FROM `security_functions` WHERE `id` = 1010;

UPDATE `security_functions` SET
`id` = `id` - 1,
`order` = `order` - 1
WHERE `id` > 1009 AND `id` < 2000;

UPDATE `security_role_functions` SET
`security_function_id` = `security_function_id` - 1
WHERE `security_function_id` > 1009 AND `security_function_id` < 2000;


DELETE FROM `labels` WHERE `module` = 'Staff' AND `field` = 'security_user_id';
DELETE FROM `labels` WHERE `module` = 'Staff' AND `field` = 'institution_site_position_id';
