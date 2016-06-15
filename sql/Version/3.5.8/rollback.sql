-- POCOR-2780
--

DROP TABLE IF EXISTS `import_mapping`;
RENAME TABLE `z_2780_import_mapping` TO `import_mapping`;

DROP TABLE IF EXISTS `security_functions`;
RENAME TABLE `z_2780_security_functions` TO `security_functions`;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2780';


-- 3.5.7
UPDATE config_items SET value = '3.5.7' WHERE code = 'db_version';
