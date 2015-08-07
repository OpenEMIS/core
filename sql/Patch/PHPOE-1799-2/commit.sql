--
-- PHPOE-1799-2 commit.sql
--

ALTER TABLE `institution_site_section_students` DROP `student_category_id`;
ALTER TABLE `institution_site_section_students` CHANGE `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';

-- Latest
ALTER TABLE `institution_site_class_students` CHANGE `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';

ALTER TABLE `institution_students` CHANGE `end_date` `end_date` DATE NOT NULL ;
ALTER TABLE `institution_students` CHANGE `end_year` `end_year` INT( 4 ) NOT NULL ;
