-- PHPOE-2084

DROP TABLE `import_mapping`;
ALTER TABLE `z2084_import_mapping` RENAME `import_mapping`;

DELETE FROM `labels` WHERE `module` = 'InstitutionSiteStudentAbsences' OR `module` = 'StaffAbsences' OR `module` = 'Imports';

DELETE FROM `db_patches` WHERE `issue`='PHPOE-2084';
