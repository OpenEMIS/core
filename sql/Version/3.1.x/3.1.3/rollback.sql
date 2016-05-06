-- security_functions
UPDATE `security_functions` SET `_execute` = NULL WHERE `id` = 5027;

-- institution_site_surveys
ALTER TABLE `institution_site_surveys` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Draft, 2 -> Completed';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1807';

ALTER TABLE `student_behaviours` CHANGE `student_id` `security_user_id` INT(11) NOT NULL COMMENT '';
ALTER TABLE `student_behaviours` CHANGE `institution_id` `institution_site_id` INT(11) NOT NULL;
ALTER TABLE `student_behaviours` DROP INDEX student_id;
ALTER TABLE `student_behaviours` DROP INDEX institution_id;
ALTER TABLE `student_behaviours` ADD INDEX(`security_user_id`);
ALTER TABLE `student_behaviours` ADD INDEX(`institution_site_id`);

ALTER TABLE `staff_behaviours` CHANGE `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '';
ALTER TABLE `staff_behaviours` CHANGE `institution_id` `institution_site_id` INT(11) NOT NULL;
ALTER TABLE `staff_behaviours` DROP INDEX staff_id;
ALTER TABLE `staff_behaviours` DROP INDEX institution_id;
ALTER TABLE `staff_behaviours` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_behaviours` ADD INDEX(`institution_site_id`);

UPDATE `config_items` SET `value` = '3.1.2' WHERE `code` = 'db_version';
