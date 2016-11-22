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
