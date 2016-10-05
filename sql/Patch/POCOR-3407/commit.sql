-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3407', NOW());


-- code here
ALTER TABLE `identity_types`
    ADD COLUMN `validation_pattern` varchar(100) DEFAULT NULL AFTER `name`;
