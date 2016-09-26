-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3215', NOW());

-- import_mapping
UPDATE `import_mapping` SET `description` = 'Education Code' WHERE `import_mapping`.`id` = 15;