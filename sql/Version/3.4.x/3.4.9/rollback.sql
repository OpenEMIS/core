-- labels
DELETE FROM `labels` WHERE `module` = 'BankAccounts' AND `field` = 'remarks';
DELETE FROM `labels` WHERE `module` = 'InstitutionBankAccounts' AND `field` = 'remarks';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1787';

-- Drop New tables
DROP TABLE IF EXISTS `institution_quality_visits`;

-- Restore table
RENAME TABLE `z_2023_institution_quality_visits` TO `institution_quality_visits`;

DELETE FROM `labels` WHERE `module` = 'InstitutionQualityVisits' AND `field` = 'file_content';
DELETE FROM `labels` WHERE `module` = 'InstitutionQualityVisits' AND `field` = 'quality_visit_type_id';
DELETE FROM `labels` WHERE `module` = 'InstitutionQualityVisits' AND `field` = 'institution_class_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2023';

--
-- PHPOE-2463
--

DROP TABLE `institution_section_students`;
ALTER TABLE `z_2463_institution_section_students` RENAME `institution_section_students`;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2463';

-- 
-- PHPOE-2436
--

DROP TABLE `import_mapping`;
ALTER TABLE `z_2436_import_mapping` RENAME `import_mapping`;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2436';

UPDATE config_items SET value = '3.4.8' WHERE code = 'db_version';
