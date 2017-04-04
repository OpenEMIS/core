-- POCOR-3851
ALTER TABLE `contact_types` DROP `validation_pattern`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3851';


-- 3.9.8.1
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.8.1' WHERE code = 'db_version';
