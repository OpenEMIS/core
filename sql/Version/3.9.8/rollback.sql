-- POCOR-3733
-- security_functions
DELETE FROM `security_functions` WHERE `id` = 6011;

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` >= 6006 AND `order` <= 6009;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3733';


-- 3.9.7
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.7' WHERE code = 'db_version';
