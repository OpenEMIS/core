-- PHPOE-2083

DROP TABLE `import_mapping`;
ALTER TABLE `z2083_import_mapping` RENAME `import_mapping`;

DELETE FROM `db_patches` WHERE `issue`='PHPOE-2083';
