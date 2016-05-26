-- custom_field_types
DELETE FROM `custom_field_types` WHERE `code` = 'REPEATER';

-- custom_modules
UPDATE `custom_modules` SET `supported_field_types` = REPLACE(`supported_field_types`, ',REPEATER', '') WHERE `model` = 'Institution.Institutions';

DELETE FROM `custom_modules` WHERE `code` = 'Institution > Repeater';

-- Restore tables
DROP TABLE IF EXISTS `institution_repeater_surveys`;
DROP TABLE IF EXISTS `institution_repeater_survey_answers`;
DROP TABLE IF EXISTS `institution_repeater_survey_table_cells`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2451';
