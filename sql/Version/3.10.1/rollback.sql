-- POCOR-3977
-- db_patches
ALTER TABLE `deleted_records`
CHANGE COLUMN `reference_key` `reference_key` CHAR(36) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL ;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3977';


-- 3.9.14
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.14' WHERE code = 'db_version';
