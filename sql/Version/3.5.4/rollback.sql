-- POCOR-2588
ALTER TABLE `academic_period_levels` DROP `editable`;

DROP TABLE institution_students;
RENAME TABLE z_2588_institution_students TO institution_students;
DROP TABLE z_2588_academic_period_parent;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2588';


-- 3.5.3
UPDATE config_items SET value = '3.5.3' WHERE code = 'db_version';
