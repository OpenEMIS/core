-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1381');

-- institution_site_survey_answer
ALTER TABLE `institution_site_survey_answers` 
ADD INDEX `survey_question_id` (`survey_question_id`);

ALTER TABLE `institution_site_survey_answers`
ADD INDEX `institution_site_survey_id` (`institution_site_survey_id`);
