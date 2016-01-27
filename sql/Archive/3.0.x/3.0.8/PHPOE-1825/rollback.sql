ALTER TABLE `student_guardians` CHANGE `student_id` `student_user_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_guardians` CHANGE `guardian_id` `guardian_user_id` INT(11) NOT NULL COMMENT 'links to security_users.id';

ALTER TABLE `security_users` DROP `is_student`;
ALTER TABLE `security_users` DROP `is_staff`;
ALTER TABLE `security_users` DROP `is_guardian`;

RENAME TABLE `z_1825_security_user_types` TO `security_user_types`;

DELETE FROM `security_functions` WHERE `id` >= 4000 AND `id` < 5000;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1825';
