-- Restore tables
DROP TABLE IF EXISTS `report_templates`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3576';
