-- POCOR-3668
-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3668';


-- 3.8.4.1
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.8.4.1' WHERE code = 'db_version';
