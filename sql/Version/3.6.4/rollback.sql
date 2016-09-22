-- POCOR-3332
-- institution_infrastructures
UPDATE `institution_infrastructures`
INNER JOIN `z_3332_institution_infrastructures` ON `z_3332_institution_infrastructures`.`id` = `institution_infrastructures`.`id`
SET `institution_infrastructures`.`code` = `z_3332_institution_infrastructures`.`code`;

DROP TABLE `z_3332_institution_infrastructures`;

-- institution_rooms
UPDATE `institution_rooms`
INNER JOIN `z_3332_institution_rooms` ON `z_3332_institution_rooms`.`id` = `institution_rooms`.`id`
SET `institution_rooms`.`code` = `z_3332_institution_rooms`.`code`;

DROP TABLE `z_3332_institution_infrastructures`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3332';


-- POCOR-3357
-- Restore tables
DROP TABLE IF EXISTS `institution_providers`;
RENAME TABLE `z_3357_institution_providers` TO `institution_providers`;

-- Delete label
DELETE FROM `labels`
WHERE `id` = '56e0a017-7bdc-11e6-92c7-525400b263eb';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3357';


-- POCOR-3347
-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3347';

DELETE FROM `translations`
WHERE `en` = 'There are no shifts configured for the selected academic period';

-- import_mapping
UPDATE `import_mapping` SET `description` = 'Code' WHERE `import_mapping`.`id` = 15;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3215';

-- 3.6.3
UPDATE config_items SET value = '3.6.3' WHERE code = 'db_version';
