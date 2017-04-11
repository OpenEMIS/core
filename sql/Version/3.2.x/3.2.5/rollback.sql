-- PHPOE-2081

DROP TABLE `import_mapping`;
ALTER TABLE `z2081_import_mapping` RENAME `import_mapping`;

DELETE FROM `db_patches` WHERE `issue`='PHPOE-2081';

UPDATE `config_items` SET `value` = '3.2.4' WHERE `code` = 'db_version';
