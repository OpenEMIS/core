-- 3.6.4.1
UPDATE config_items SET value = '3.6.4.1' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
