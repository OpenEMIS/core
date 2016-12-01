DROP TABLE IF EXISTS `textbooks`;

DROP TABLE IF EXISTS `textbook_conditions`;

DROP TABLE IF EXISTS `textbook_statuses`;

DROP TABLE IF EXISTS `institution_textbooks`;

DELETE FROM `labels`
WHERE `module` = 'InstitutionTextbooks';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3568';