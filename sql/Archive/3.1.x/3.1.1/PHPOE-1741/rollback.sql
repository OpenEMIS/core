DROP TABLE labels;
RENAME TABLE z_1741_labels TO labels;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1741';
