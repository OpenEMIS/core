-- 3.5.3
UPDATE config_items SET value = '3.5.3' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
