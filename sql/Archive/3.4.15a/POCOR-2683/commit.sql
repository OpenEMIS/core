-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2683', NOW());

-- patch Infrastructure
DROP PROCEDURE IF EXISTS patchSurveyParams;
DELIMITER $$

CREATE PROCEDURE patchSurveyParams()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE questionId INT(11);
  DECLARE paramKey, paramValue VARCHAR(100);
  DECLARE survey_params CURSOR FOR
  		SELECT `SurveyQuestionParams`.`survey_question_id`, `SurveyQuestionParams`.`param_key`, `SurveyQuestionParams`.`param_value`
  		FROM `survey_question_params` AS `SurveyQuestionParams`
  		ORDER BY `SurveyQuestionParams`.`survey_question_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN survey_params;

  read_loop: LOOP
    FETCH survey_params INTO questionId, paramKey, paramValue;
    IF done THEN
      LEAVE read_loop;
    END IF;

	UPDATE `survey_questions` SET `params` = CONCAT('{"', paramKey, '":"', paramValue, '"}') WHERE `id` =  questionId;

  END LOOP read_loop;

  CLOSE survey_params;
END
$$

DELIMITER ;

CALL patchSurveyParams;

DROP PROCEDURE IF EXISTS patchSurveyParams;

-- Backup table
RENAME TABLE `survey_question_params` TO `z_2683_survey_question_params`;
