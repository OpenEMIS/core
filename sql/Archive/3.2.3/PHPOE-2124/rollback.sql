-- PHPOE-2084
DROP TABLE `import_mapping`;
ALTER TABLE `z2084_import_mapping` RENAME `import_mapping`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2084';
