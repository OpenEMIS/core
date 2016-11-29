-- Restore tables
DROP TABLE IF EXISTS `excel_templates`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3576';
