-- 3.9.14
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.14' WHERE code = 'db_version';
