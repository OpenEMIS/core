-- institution_classes
DROP TABLE IF EXISTS `institution_classes`;
RENAME TABLE `z_4042_institution_classes` TO `institution_classes`;


-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-4042';
