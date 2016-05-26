-- PHPOE-2359

DROP TABLE `import_mapping`;
ALTER TABLE `z_2359_import_mapping` RENAME `import_mapping`;

DELETE FROM `labels` WHERE `module`='Imports' AND `field`='institution_id';

DELETE FROM `db_patches` WHERE `issue`='PHPOE-2359';
