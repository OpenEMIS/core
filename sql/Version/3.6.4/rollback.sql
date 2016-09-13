-- POCOR-3347
-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3347';

DELETE FROM `translations`
WHERE `en` = 'There are no shifts configured for the selected academic period';


-- 3.6.3
UPDATE config_items SET value = '3.6.3' WHERE code = 'db_version';
