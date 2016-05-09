-- Restore tables
DROP TABLE IF EXISTS `assessments`;
RENAME TABLE `z_2759_assessments` TO `assessments`;

DROP TABLE IF EXISTS `assessment_items`;
RENAME TABLE `z_2759_assessment_items` TO `assessment_items`;

DROP TABLE IF EXISTS `assessment_item_results`;
RENAME TABLE `z_2759_assessment_item_results` TO `assessment_item_results`;

DROP TABLE IF EXISTS `assessment_grading_types`;
RENAME TABLE `z_2759_assessment_grading_types` TO `assessment_grading_types`;

DROP TABLE IF EXISTS `assessment_grading_options`;
RENAME TABLE `z_2759_assessment_grading_options` TO `assessment_grading_options`;

DROP TABLE IF EXISTS `assessment_periods`;

RENAME TABLE `z_2759_assessment_statuses` TO `assessment_statuses`;
RENAME TABLE `z_2759_assessment_status_periods` TO `assessment_status_periods`;
RENAME TABLE `z_2759_institution_assessments` TO `institution_assessments`;

DROP TABLE IF EXISTS `institution_subject_students`;
RENAME TABLE `z_2759_institution_subject_students` TO `institution_subject_students`;

-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Assessments.view', `_edit` = 'Assessments.edit' WHERE `id` = 1015;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2759';
