-- Restore table
DROP TABLE `institution_infrastructures`;
RENAME TABLE `z_2571_institution_infrastructures` TO `institution_infrastructures`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2571';
