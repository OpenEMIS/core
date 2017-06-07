-- POCOR-1330
-- education_grades
DROP TABLE IF EXISTS `education_grades`;
RENAME TABLE `z_1330_education_grades` TO `education_grades`;

-- education_stages
DROP TABLE IF EXISTS `education_stages`;

-- security_functions
DROP TABLE IF EXISTS `security_functions`;
RENAME TABLE `z_1330_security_functions` TO `security_functions`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-1330';


-- 3.10.1
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.10.1' WHERE code = 'db_version';
