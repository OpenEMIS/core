-- import_mapping
UPDATE `import_mapping` SET `description` = 'Code' WHERE `import_mapping`.`id` = 15;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3215';