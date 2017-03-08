-- POCOR-3407
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3407', NOW());


-- code here
ALTER TABLE `identity_types`
    ADD COLUMN `validation_pattern` varchar(100) DEFAULT NULL AFTER `name`;


-- 3.6.5.1
UPDATE config_items SET value = '3.6.5.1' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
