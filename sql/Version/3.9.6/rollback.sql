-- POCOR-3680
-- security_group_users
DROP TABLE IF EXISTS `security_group_users`;
RENAME TABLE `z_3680_security_group_users` TO `security_group_users`;

DROP TABLE IF EXISTS `institution_staff`;
RENAME TABLE `z_3680_institution_staff` TO `institution_staff`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3680';


-- POCOR-3849
-- room_types
DROP TABLE IF EXISTS `room_types`;
RENAME TABLE `z_3849_room_types` TO `room_types`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3849';


-- POCOR-3857
-- security_functions
DELETE FROM `config_items` WHERE `id` IN (126, 127);

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3857';

-- 3.9.5
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.5' WHERE code = 'db_version';
