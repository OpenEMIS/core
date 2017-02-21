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
