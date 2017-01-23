-- competency
DROP TABLE IF EXISTS `competency_grading_types`;
DROP TABLE IF EXISTS `competency_grading_options`;
DROP TABLE IF EXISTS `competency_templates`;
DROP TABLE IF EXISTS `competency_items`;
DROP TABLE IF EXISTS `competency_criterias`;
DROP TABLE IF EXISTS `competency_periods`;
DROP TABLE IF EXISTS `competency_criteria_results`;

-- system_patches
DELETE FROM `system_patches` WHERE 'issue' = 'POCOR-3342';
