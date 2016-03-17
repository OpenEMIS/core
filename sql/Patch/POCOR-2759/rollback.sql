-- Restore tables
DROP TABLE IF EXISTS `assessments`;
RENAME TABLE `z_2759_assessments` TO `assessments`;

DROP TABLE IF EXISTS `assessment_items`;
RENAME TABLE `z_2759_assessment_items` TO `assessment_items`;

DROP TABLE IF EXISTS `assessment_item_results`;
RENAME TABLE `z_2759_assessment_item_results` TO `assessment_item_results`;

DROP TABLE IF EXISTS `assessment_grading_types`;
RENAME TABLE `z_2759_assessment_grading_types` TO `assessment_grading_types`;

RENAME TABLE `z_2759_assessment_statuses` TO `assessment_statuses`;
RENAME TABLE `z_2759_assessment_status_periods` TO `assessment_status_periods`;
RENAME TABLE `z_2759_institution_assessments` TO `institution_assessments`;

-- assessment_periods
DROP TABLE IF EXISTS `assessment_periods`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2759';
