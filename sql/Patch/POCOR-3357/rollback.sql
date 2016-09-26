-- Restore tables
DROP TABLE IF EXISTS `institution_providers`;
RENAME TABLE `z_3357_institution_providers` TO `institution_providers`;

-- Delete label
DELETE FROM `labels`
WHERE `id` = '56e0a017-7bdc-11e6-92c7-525400b263eb';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3357';