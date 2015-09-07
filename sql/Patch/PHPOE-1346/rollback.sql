-- Drop tables
DROP TABLE IF EXISTS `institution_student_surveys`;
DROP TABLE IF EXISTS `institution_student_survey_answers`;
DROP TABLE IF EXISTS `institution_student_survey_table_cells`;

-- Drop Student List
DELETE FROM `custom_field_types` WHERE `code` = 'STUDENT_LIST';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1346';
