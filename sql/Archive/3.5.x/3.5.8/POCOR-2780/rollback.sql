--
-- POCOR-2780
-- 

DELETE FROM `import_mapping` WHERE `id` IN (81, 82, 83, 84, 85, 86);
DELETE FROM `security_functions` WHERE `id` = 1042;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2780';
