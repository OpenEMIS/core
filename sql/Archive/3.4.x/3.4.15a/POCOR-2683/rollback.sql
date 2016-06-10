-- survey_questions
UPDATE `survey_questions` SET `params` = NULL WHERE `field_type` =  'STUDENT_LIST';

-- Restore table
RENAME TABLE `z_2683_survey_question_params` TO `survey_question_params`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2683';
