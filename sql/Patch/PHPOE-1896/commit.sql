

ALTER TABLE `student_behaviours` CHANGE `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_behaviours` CHANGE `institution_site_id` `institution_id` INT(11) NOT NULL;
ALTER TABLE `student_behaviours` DROP INDEX security_user_id;
ALTER TABLE `student_behaviours` DROP INDEX institution_site_id;
ALTER TABLE `student_behaviours` ADD INDEX(`student_id`);
ALTER TABLE `student_behaviours` ADD INDEX(`institution_id`);
