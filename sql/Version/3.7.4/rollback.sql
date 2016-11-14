-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2737';


-- 3.7.3
UPDATE config_items SET value = '3.7.3' WHERE code = 'db_version';
