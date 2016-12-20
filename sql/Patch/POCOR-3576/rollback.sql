-- Restore tables
DROP TABLE IF EXISTS `excel_templates`;

-- labels
DELETE FROM `labels` WHERE `id` = 'ad8fa33a-c0d8-11e6-90e8-525400b263eb';

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 5059;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3576';
