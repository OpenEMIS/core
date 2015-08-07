--
-- PHPOE-1799-2 commit.sql
--

ALTER TABLE `institution_site_section_students` DROP `student_category_id`;
ALTER TABLE `institution_site_section_students` CHANGE `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';

-- Latest
ALTER TABLE `institution_site_class_students` CHANGE `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';

ALTER TABLE `institution_students` CHANGE `end_date` `end_date` DATE NOT NULL ;
ALTER TABLE `institution_students` CHANGE `end_year` `end_year` INT( 4 ) NOT NULL ;

INSERT INTO `security_functions`
(`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1026, 'Grades', 'Institutions', 'Institutions', 'Details', 1000, 'Grades.index', NULL, NULL, NULL, 'Grades.indexEdit', 1026, 1, NULL, NULL, 1, '0000-00-00 00:00:00');
