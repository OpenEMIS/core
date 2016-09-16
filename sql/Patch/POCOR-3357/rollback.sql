-- Restore tables
DROP TABLE IF EXISTS `institution_providers`;
RENAME TABLE `z_3357_institution_providers` TO `institution_providers`;

-- Delete label
DELETE FROM `labels`
WHERE `module` = 'Providers'
AND `field` = 'institution_sector_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3357';