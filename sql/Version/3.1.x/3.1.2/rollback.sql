-- 
-- PHPOE-1948 rollback.sql
-- 

UPDATE `security_groups`
INNER JOIN `institution_sites` ON `security_groups`.`id` = `institution_sites`.`security_group_id`
SET `security_groups`.`name`=`institution_sites`.`name`
WHERE `institution_sites`.`security_group_id`=`security_groups`.`id`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='PHPOE-1948';

UPDATE `config_items` SET `value` = '3.1.1' WHERE `code` = 'db_version';
