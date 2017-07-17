-- POCOR-4089
-- education_grades
DROP TABLE IF EXISTS `education_grades`;
RENAME TABLE `z_4089_education_grades` TO `education_grades`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-4089';


-- 3.10.7
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.10.7' WHERE code = 'db_version';
