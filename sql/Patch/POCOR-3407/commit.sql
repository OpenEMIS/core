-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3407', NOW());


-- code here
ALTER TABLE `identity_types`
    ADD COLUMN `validation_pattern` varchar(100) DEFAULT NULL AFTER `name`;

UPDATE `identity_types` SET `validation_pattern` = '^[a-z]{6,}+$' WHERE `name` = 'Birth Certificate';
UPDATE `identity_types` SET `validation_pattern` = '^[A-Z]{1}[0-9]{6}$' WHERE `name` = 'Passport';
