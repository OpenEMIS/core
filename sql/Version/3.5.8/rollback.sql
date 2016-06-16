-- POCOR-3037
-- code here
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 7047;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3037';


-- POCOR-2780
--

DELETE FROM `import_mapping` WHERE `id` IN (81, 82, 83, 84, 85, 86);
DELETE FROM `security_functions` WHERE `id` = 1042;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2780';


-- POCOR-2416
DROP TABLE deleted_records;

-- db_patches
DELETE FROM db_patches WHERE `issue` = 'POCOR-2416';

-- 3.5.7
UPDATE config_items SET value = '3.5.7' WHERE code = 'db_version';
