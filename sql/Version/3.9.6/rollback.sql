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
