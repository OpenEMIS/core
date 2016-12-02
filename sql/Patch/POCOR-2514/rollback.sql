-- config items
UPDATE `import_mapping` SET `description` = '( DD/MM/YYYY )' WHERE `column_name` LIKE '%date%';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2514';
