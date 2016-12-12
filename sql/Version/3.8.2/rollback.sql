-- POCOR-3457
-- assessment_grading_options
ALTER TABLE `assessment_grading_options` DROP `description`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3457';


-- 3.8.1.1
UPDATE config_items SET value = '3.8.1.1' WHERE code = 'db_version';
