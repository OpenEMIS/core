ALTER TABLE `academic_period_levels` DROP `editable`;

-- db_patches
DELETE `db_patches` WHERE `issue` = 'POCOR-2588';