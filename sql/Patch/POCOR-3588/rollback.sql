-- examination_items
DROP TABLE IF EXISTS `examination_items`;
RENAME TABLE `z_3588_examination_items` TO `examination_items`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3588';
