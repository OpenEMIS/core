-- room_types
DROP TABLE IF EXISTS `room_types`;
RENAME TABLE `z_3849_room_types` TO `room_types`;


-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3849';
