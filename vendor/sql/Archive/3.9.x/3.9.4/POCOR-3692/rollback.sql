-- examination_item_results
DROP TABLE IF EXISTS `examination_item_results`;
RENAME TABLE `z_3692_examination_item_results` TO `examination_item_results`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3692';
