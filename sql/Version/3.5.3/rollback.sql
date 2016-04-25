-- POCOR-2863
-- institution_class_students
DROP TABLE `institution_class_students`;

ALTER TABLE `z_2863_institution_class_students`
RENAME TO  `institution_class_students`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2863';


-- 3.5.2
UPDATE config_items SET value = '3.5.2' WHERE code = 'db_version';
