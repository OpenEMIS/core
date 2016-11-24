-- POCOR-3468
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3468', NOW());

-- config_product_lists
ALTER TABLE `config_product_lists`
ADD COLUMN `deletable` INT(1) NOT NULL DEFAULT 1 AFTER `url`,
ADD COLUMN `file_name` VARCHAR(250) NULL AFTER `deletable`,
ADD COLUMN `file_content` LONGBLOB NULL AFTER `file_name`;

UPDATE `config_product_lists`
SET `deletable` = 0;


-- POCOR-3312
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3312', NOW());


-- Translations
INSERT INTO `translations` (`code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `editable`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES (NULL, 'There are no shifts configured for the selected academic period, will be using system configuration timing', NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, '2', NOW());


-- POCOR-3427
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3427', NOW());

-- institutions
ALTER TABLE `institutions`
CHANGE `is_academic` `classification` INT(1) NOT NULL DEFAULT '1' COMMENT '0 -> Non-academic institution, 1 -> Academic Institution';


-- POCOR-3525
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3525', NOW());

UPDATE `import_mapping`
SET `lookup_plugin` = 'Institution', `lookup_model` = 'StudentUser'
WHERE `model` = 'Institution.Students' AND `column_name` = 'student_id';


-- 3.7.5
UPDATE config_items SET value = '3.7.5' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
