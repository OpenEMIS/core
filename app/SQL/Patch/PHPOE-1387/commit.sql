--
-- PHPOE-1387 commit.sql
-- 

ALTER TABLE `institution_sites` ADD `security_group_id` INT(11) NULL DEFAULT NULL AFTER `institution_site_gender_id`,
ADD INDEX `security_group_id` (`security_group_id`) ;