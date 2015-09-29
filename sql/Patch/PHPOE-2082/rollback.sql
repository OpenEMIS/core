-- PHPOE-2082

DROP TABLE `import_mapping`;
ALTER TABLE `z2082_import_mapping` RENAME `import_mapping`;

DELETE FROM `db_patches` WHERE `issue`='PHPOE-2082';
