-- POCOR-3870
-- `institution_subject_students`
DROP TABLE IF EXISTS `institution_subject_students`;

RENAME TABLE `z_3870_institution_subject_students_1` TO `institution_subject_students`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3870';


-- 3.9.9
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.9' WHERE code = 'db_version';
