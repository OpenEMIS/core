-- POCOR-3468
-- config_product_lists
ALTER TABLE `config_product_lists`
DROP COLUMN `file_content`,
DROP COLUMN `file_name`,
DROP COLUMN `deletable`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3468';


-- POCOR-3312
-- config items
DELETE FROM `translations` WHERE `en` = 'There are no shifts configured for the selected academic period, will be using system configuration timing';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3312';


-- POCOR-3427
-- institutions
ALTER TABLE `institutions`
CHANGE `classification` `is_academic` INT(1) NOT NULL DEFAULT '1' COMMENT '0 -> Non-academic institution, 1 -> Academic Institution';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3427';


-- POCOR-3525
UPDATE `import_mapping`
SET `lookup_plugin` = 'Student', `lookup_model` = 'Students'
WHERE `model` = 'Institution.Students' AND `column_name` = 'student_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3525';


-- 3.7.4
UPDATE config_items SET value = '3.7.4' WHERE code = 'db_version';
