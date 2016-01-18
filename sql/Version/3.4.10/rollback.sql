DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2168';

UPDATE config_items SET value = '3.4.9' WHERE code = 'db_version';
