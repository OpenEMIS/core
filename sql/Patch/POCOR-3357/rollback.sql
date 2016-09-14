-- Restore tables
DROP TABLE IF EXISTS `institution_providers`;
RENAME TABLE `z_3357_institution_providers` TO `institution_providers`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3357';