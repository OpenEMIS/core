-- Drop New tables
DROP TABLE IF EXISTS `institution_quality_visits`;

-- Restore table
RENAME TABLE `z_2023_institution_quality_visits` TO `institution_quality_visits`;

DELETE FROM `labels` WHERE `module` = 'InstitutionQualityVisits' AND `field` = 'file_content';
DELETE FROM `labels` WHERE `module` = 'InstitutionQualityVisits' AND `field` = 'quality_visit_type_id';
DELETE FROM `labels` WHERE `module` = 'InstitutionQualityVisits' AND `field` = 'institution_class_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2023';
