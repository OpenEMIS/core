-- PHPOE-2366

DROP TABLE `import_mapping`;
ALTER TABLE `z_2366_import_mapping` RENAME `import_mapping`;

DELETE FROM `db_patches` WHERE `issue`='PHPOE-2366';
