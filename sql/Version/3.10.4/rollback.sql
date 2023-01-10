-- POCOR-4061
-- system_errors
ALTER TABLE `system_errors`
CHANGE COLUMN `code` `code` INT(5) NULL ;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-4061';


-- POCOR-4042
-- institution_classes
DROP TABLE IF EXISTS `institution_classes`;
RENAME TABLE `z_4042_institution_classes` TO `institution_classes`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-4042';


-- 3.10.3
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.10.3' WHERE code = 'db_version';
