-- PHPOE-2084

DROP TABLE `import_mapping`;
ALTER TABLE `z2084_import_mapping` RENAME `import_mapping`;

DELETE FROM `labels` WHERE `module` = 'InstitutionSiteStudentAbsences' OR `module` = 'StaffAbsences' OR `module` = 'Imports';

DELETE FROM `db_patches` WHERE `issue`='PHPOE-2084';

-- config_items
DELETE FROM `config_items` WHERE `code` = 'support_url';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2099';

-- security_functions
UPDATE `security_functions` SET `_execute`=null WHERE `id`=1025;
UPDATE `security_functions` SET `_execute`=null WHERE `id`=1029;
DELETE FROM `security_functions` WHERE `id`=6003;
DELETE FROM `security_functions` WHERE `id`=6004;
DELETE FROM `security_functions` WHERE `id`=6006;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2281';

UPDATE `config_items` SET `value` = '3.3.3' WHERE `code` = 'db_version';
