-- POCOR-3995
-- education_grades_subjects
DROP TABLE IF EXISTS `education_grades_subjects`;
RENAME TABLE `z_3995_education_grades_subjects` TO `education_grades_subjects`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3995';


-- 3.10.6
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.10.6' WHERE code = 'db_version';
