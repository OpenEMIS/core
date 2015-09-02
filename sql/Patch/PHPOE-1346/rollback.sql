-- New tables
DROP TABLE IF EXISTS `institution_student_surveys`;
DROP TABLE IF EXISTS `institution_student_survey_answers`;
DROP TABLE IF EXISTS `institution_student_survey_table_cells`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1346';
