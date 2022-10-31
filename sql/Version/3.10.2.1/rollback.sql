-- 3.10.2
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.10.2' WHERE code = 'db_version';
