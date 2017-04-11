ALTER TABLE `academic_period_levels` DROP `editable`;

DROP TABLE institution_students;
RENAME TABLE z_2588_institution_students TO institution_students;
DROP TABLE z_2588_academic_period_parent;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2588';