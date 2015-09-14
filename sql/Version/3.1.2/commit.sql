-- 
-- PHPOE-1948 commit.sql
-- 

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1948');


UPDATE `security_groups`
INNER JOIN `institution_sites` ON `security_groups`.`id` = `institution_sites`.`security_group_id`
SET `security_groups`.`name`=CONCAT(`institution_sites`.`code`, ' - ', `institution_sites`.`name`)
WHERE `institution_sites`.`security_group_id`=`security_groups`.`id`;

UPDATE `config_items` SET `value` = '3.1.2' WHERE `code` = 'db_version';
