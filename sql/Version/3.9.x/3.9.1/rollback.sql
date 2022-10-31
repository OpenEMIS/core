-- POCOR-3459
-- institutions
ALTER TABLE `institutions`
MODIFY COLUMN `classification` INT(1) NOT NULL DEFAULT '1' COMMENT '0 -> Non-academic institution, 1 -> Academic Institution';

UPDATE `institutions`
SET `classification` = 0
WHERE `classification` = 2;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3459';


-- POCOR-3342
-- competency
DROP TABLE IF EXISTS `competency_grading_types`;
DROP TABLE IF EXISTS `competency_grading_options`;
DROP TABLE IF EXISTS `competency_templates`;
DROP TABLE IF EXISTS `competency_items`;
DROP TABLE IF EXISTS `competency_criterias`;
DROP TABLE IF EXISTS `competency_periods`;
DROP TABLE IF EXISTS `competency_items_periods`;
DROP TABLE IF EXISTS `competency_results`;

-- labels
DELETE FROM `labels` WHERE `id` IN ('0e77e3d5-e39d-11e6-a064-525400b263eb', '7daa7045-e920-11e6-b872-525400b263eb', 'd24f6444-e922-11e6-b872-525400b263eb');

-- security_functions
DELETE FROM `security_functions` WHERE `id` IN (1053, 5061, 5062, 5063);

UPDATE `security_functions`
SET `order` = `order` - 3
WHERE `order` >= 5056 AND `order` < 6000;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3342';


-- POCOR-3562
-- restore assessment_item_results
DROP TABLE IF EXISTS `assessment_item_results`;
RENAME TABLE `z_3562_assessment_item_results` TO `assessment_item_results`;

-- restore institution_subject_students
DROP TABLE IF EXISTS `institution_subject_students`;
RENAME TABLE `z_3562_institution_subject_students` TO `institution_subject_students`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3562';


-- 3.8.8
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.8.8' WHERE code = 'db_version';
