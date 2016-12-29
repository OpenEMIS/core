DROP TABLE IF EXISTS `system_updates`;
DELETE FROM config_items WHERE id = 200;
RENAME TABLE `system_patches` TO `db_patches`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3527';
