-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2514', NOW());

-- Translations
UPDATE `import_mapping` SET `description` = NULL WHERE `description` = '( DD/MM/YYYY )';
