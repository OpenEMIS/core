-- examination_item_results
DROP TABLE IF EXISTS `institutions`;
RENAME TABLE `z_3570_institutions` TO `institutions`;


-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3570';
