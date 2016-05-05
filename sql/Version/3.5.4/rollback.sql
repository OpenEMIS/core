-- POCOR-2450
DELETE FROM `custom_field_types` WHERE `id`=10;

DROP TABLE IF EXISTS `custom_modules`;
RENAME TABLE `z_2450_custom_modules` TO `custom_modules`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2450';


-- POCOR-2588
ALTER TABLE `academic_period_levels` DROP `editable`;

DROP TABLE institution_students;
RENAME TABLE z_2588_institution_students TO institution_students;
DROP TABLE z_2588_academic_period_parent;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2588';


-- 3.5.3
UPDATE config_items SET value = '3.5.3' WHERE code = 'db_version';
