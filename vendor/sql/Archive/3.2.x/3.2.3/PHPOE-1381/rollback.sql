
-- institution_site_survey_answer
ALTER TABLE `institution_site_survey_answers` 
DROP INDEX `institution_site_survey_id`;

ALTER TABLE `institution_site_survey_answers` 
DROP INDEX `survey_question_id` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1381';