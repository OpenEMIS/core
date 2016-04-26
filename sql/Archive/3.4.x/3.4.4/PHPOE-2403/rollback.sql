--
-- PHPOE-2403
--

DROP TABLE `import_mapping`;
ALTER TABLE `z_2403_import_mapping` RENAME `import_mapping`;

DROP TABLE `labels`;
ALTER TABLE `z_2403_labels` RENAME `labels`;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2403';
