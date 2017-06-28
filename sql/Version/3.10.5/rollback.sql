-- POCOR-3809
-- security_users
DROP TABLE IF EXISTS `security_users`;
RENAME TABLE `z_3809_security_users` TO `security_users`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3809';


-- POCOR-3955
-- security_functions
DROP TABLE IF EXISTS `security_functions`;
RENAME TABLE `z_3955_security_functions` TO `security_functions`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3955';


-- 3.10.4
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.10.4' WHERE code = 'db_version';
