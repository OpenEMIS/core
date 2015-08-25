INSERT INTO `db_patches` VALUES ('PHPOE-1882');

UPDATE `security_functions` SET `order` = 1002 WHERE `id` = 1001;
UPDATE `security_functions` SET `order` = 1001 WHERE `id` = 1002;
UPDATE `security_functions` SET `order` = 1006 WHERE `id` = 1004;
UPDATE `security_functions` SET `order` = 1004 WHERE `id` = 1006;

UPDATE `security_functions` SET `category` = 'Staff', `order` = 1016 WHERE `id` = 1003;
UPDATE `security_functions` SET `category` = 'Academic' WHERE `id` IN (1004, 1006, 1007, 1008, 1009, 1010);
UPDATE `security_functions` SET 
`name` = 'Promotion', 
`category` = 'Students',
`_view` = NULL,
`_execute` = 'Promotion.index|Promotion.indexEdit',
`order` = 1024
WHERE `id` = 1005;

UPDATE `security_functions` SET `category` = 'General', `order` = 2002 WHERE `id` = 2010;
UPDATE `security_functions` SET `category` = 'Academic' WHERE `id` IN (2007, 2011, 2012, 2013, 2014, 2015, 2016, 2017);
UPDATE `security_functions` SET `order` = 2017 WHERE `id` = 2007;

UPDATE `security_functions` SET `category` = 'Career' WHERE `id` IN (3012, 3013, 3014, 3015, 3016, 3017, 3019);
UPDATE `security_functions` SET `category` = 'Career', `order` = 3018 WHERE `id` = 3007;
UPDATE `security_functions` SET `category` = 'Professional Development' WHERE `id` IN (3010, 3011, 3018, 3021, 3022);
UPDATE `security_functions` SET `name` = 'Salaries', `category` = 'Finance' WHERE `id` = 3020;

UPDATE `security_functions` SET `name` = 'Trainings' WHERE `id` = 3011;
RENAME TABLE `staff_training` TO `staff_trainings`;
ALTER TABLE `staff_trainings` CHANGE `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_trainings` DROP INDEX security_user_id;
ALTER TABLE `staff_trainings` ADD INDEX(`staff_id`);

INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES
('Guardians', 'openemis_no', 'OpenEMIS ID', 1, NOW()),
('Guardians', 'photo_content', 'Photo', 1, NOW());

UPDATE `student_statuses` SET `name` = 'Enrolled' WHERE `student_statuses`.`id` = 1;

DELETE FROM `security_functions` WHERE `id` >= 6000;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(6000, 'Institution', 'Reports', 'Reports', 'Reports', -1, 'Institutions.index', NULL, 'Institutions.add', NULL, 'Institutions.download', 6000, 1, 1, NOW()),
(6001, 'Students', 'Reports', 'Reports', 'Reports', -1, 'Students.index', NULL, 'Students.add', NULL, 'Students.download', 6001, 1, 1, NOW()),
(6002, 'Staff', 'Reports', 'Reports', 'Reports', -1, 'Staff.index', NULL, 'Staff.add', NULL, 'Staff.download', 6002, 1, 1, NOW());

DELETE FROM `student_statuses` WHERE `code` = 'EXPELLED';

UPDATE `security_functions` SET `_execute` = 'Students.excel' WHERE `id` = 1012;
UPDATE `security_functions` SET `_execute` = 'Staff.excel' WHERE `id` = 1016;

