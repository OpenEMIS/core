-- security_functions
UPDATE `security_functions` SET `_view` = 'Students.index|Students.view|StudentUser.view', `_edit` = 'Students.edit|StudentUser.edit' WHERE `id` = 1012;

-- Drop tables
DROP TABLE IF EXISTS `institution_student_surveys`;
DROP TABLE IF EXISTS `institution_student_survey_answers`;
DROP TABLE IF EXISTS `institution_student_survey_table_cells`;

DROP TABLE IF EXISTS `survey_question_params`;

-- Drop Student List
ALTER TABLE `custom_modules` DROP `supported_field_types`;

DELETE FROM `custom_modules` WHERE `model` = 'Student.StudentSurveys';
DELETE FROM `custom_modules` WHERE `model` = 'Staff.StaffSurveys';

-- Drop Student List
DELETE FROM `custom_field_types` WHERE `code` = 'STUDENT_LIST';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1346';
