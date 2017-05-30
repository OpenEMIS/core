-- assessment_items
DROP TABLE IF EXISTS `assessment_items`;
RENAME TABLE `z_3720_assessment_items` TO `assessment_items`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3720';
