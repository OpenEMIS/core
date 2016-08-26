DROP TABLE IF EXISTS `assessment_items_grading_types`;

DROP TABLE IF EXISTS `assessment_items`;

RENAME TABLE `z_3080_assessment_items` TO `assessment_items`;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3080';