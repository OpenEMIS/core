DROP TABLE labels;
RENAME TABLE z_1741_labels TO labels;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1741';

UPDATE `config_items` SET `value` = '3.0.9' WHERE `code` = 'db_version';
