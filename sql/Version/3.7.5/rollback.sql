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
