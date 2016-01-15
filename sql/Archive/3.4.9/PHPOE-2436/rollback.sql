-- 
-- PHPOE-2436
--

DROP TABLE `import_mapping`;
ALTER TABLE `z_2436_import_mapping` RENAME `import_mapping`;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2436';