ALTER TABLE `student_guardians` CHANGE `student_id` `student_user_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_guardians` CHANGE `guardian_id` `guardian_user_id` INT(11) NOT NULL COMMENT 'links to security_users.id';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1825';
