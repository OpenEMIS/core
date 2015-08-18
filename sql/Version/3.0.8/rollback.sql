ALTER TABLE `student_guardians` CHANGE `student_id` `student_user_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_guardians` CHANGE `guardian_id` `guardian_user_id` INT(11) NOT NULL COMMENT 'links to security_users.id';

ALTER TABLE `security_users` DROP `is_student`;
ALTER TABLE `security_users` DROP `is_staff`;
ALTER TABLE `security_users` DROP `is_guardian`;

RENAME TABLE `z_1825_security_user_types` TO `security_user_types`;

DELETE FROM `security_functions` WHERE `id` >= 4000 AND `id` < 5000;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1825';

UPDATE `security_functions` SET `order` = 1001 WHERE `id` = 1001;
UPDATE `security_functions` SET `order` = 1002 WHERE `id` = 1002;
UPDATE `security_functions` SET `order` = 1004 WHERE `id` = 1004;
UPDATE `security_functions` SET `order` = 1006 WHERE `id` = 1006;

UPDATE `security_functions` SET `category` = 'Details', `order` = 1003 WHERE `id` = 1003;
UPDATE `security_functions` SET `category` = 'Details' WHERE `id` IN (1004, 1006, 1007, 1008, 1009, 1010);
UPDATE `security_functions` SET 
`name` = 'Grades', 
`category` = 'Details',
`_view` = 'Grades.index',
`_execute` = 'Grades.indexEdit',
`order` = 1005
WHERE `id` = 1005;

UPDATE `security_functions` SET `category` = 'Details', `order` = 2010 WHERE `id` = 2010;
UPDATE `security_functions` SET `category` = 'Details' WHERE `id` IN (2011, 2012, 2013, 2014, 2015, 2016, 2017);
UPDATE `security_functions` SET `category` = 'General', `order` = 2007 WHERE `id` = 2007;

UPDATE `security_functions` SET `category` = 'Details' WHERE `id` IN (3012, 3013, 3014, 3015, 3016, 3017, 3019);
UPDATE `security_functions` SET `category` = 'General', `order` = 3007 WHERE `id` = 3007;
UPDATE `security_functions` SET `category` = 'Details' WHERE `id` IN (3010, 3011, 3018, 3020, 3021, 3022);

ALTER TABLE `staff_trainings` CHANGE `staff_id` `security_user_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_trainings` DROP INDEX staff_id;
ALTER TABLE `staff_trainings` ADD INDEX(`security_user_id`);
RENAME TABLE `staff_trainings` TO `staff_training`;

DELETE FROM `labels` WHERE `module` = 'Guardians' AND `field` = 'openemis_no';
DELETE FROM `labels` WHERE `module` = 'Guardians' AND `field` = 'photo_content';

UPDATE `student_statuses` SET `name` = 'Current' WHERE `student_statuses`.`id` = 1;

DELETE FROM `security_functions` WHERE `id` >= 6000;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1882';

UPDATE `config_items` SET `value` = '3.0.7' WHERE `code` = 'db_version';
