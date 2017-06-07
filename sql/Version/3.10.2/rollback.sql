-- POCOR-3996
-- `import_mapping`
DELETE FROM `import_mapping`
WHERE `model` = 'Training.TrainingSessionsTrainees';

-- `labels`
DELETE FROM `labels`
WHERE `id` = '6c3d2497-4b27-11e7-9846-525400b263eb';

-- `system_patches`
DELETE FROM `system_patches` WHERE `issue`='POCOR-3996';


-- POCOR-3983
-- institution_statuses
DROP TABLE IF EXISTS `institution_statuses`;
RENAME TABLE `z_3983_institution_statuses` TO `institution_statuses`;

-- institutions
DROP TABLE IF EXISTS `institutions`;
RENAME TABLE `z_3983_institutions` TO `institutions`;

-- import_mapping
DROP TABLE IF EXISTS `import_mapping`;
RENAME TABLE `z_3983_import_mapping` TO `import_mapping`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3983';


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
