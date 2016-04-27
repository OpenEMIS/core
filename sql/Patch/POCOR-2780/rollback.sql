--
-- POCOR-2780
-- 

DROP TABLE IF EXISTS `import_mapping`;
RENAME TABLE `z_2780_import_mapping` TO `import_mapping`;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2780';
