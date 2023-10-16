-- institution_rooms
DROP TABLE IF EXISTS `institution_rooms`;

RENAME TABLE `z_3663_institution_rooms` TO `institution_rooms`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3663';
