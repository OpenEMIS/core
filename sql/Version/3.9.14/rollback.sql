-- POCOR-3905
-- `institution_genders`
DROP TABLE IF EXISTS `education_grades`;
RENAME TABLE `z_3905_education_grades` TO `education_grades`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3905';


-- 3.9.13.1
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.13.1' WHERE code = 'db_version';
