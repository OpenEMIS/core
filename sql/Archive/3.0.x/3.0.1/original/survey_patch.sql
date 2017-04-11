-- 14th July 2015

ALTER TABLE `custom_field_types` ADD `visible` INT(1) NOT NULL DEFAULT '1' AFTER `is_unique`;
UPDATE `custom_field_types` SET `visible` = 1;
UPDATE `custom_field_types` SET `visible` = 0 WHERE `code` IN ('DATE', 'TIME');
UPDATE `custom_field_types` SET `value` = 'number_value' WHERE `code` = 'CHECKBOX';

RENAME TABLE `survey_form_questions` TO `survey_forms_questions`;
ALTER TABLE `survey_forms_questions` ADD `section` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `survey_question_id`;
ALTER TABLE `survey_forms_questions` CHANGE `name` `name` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

-- patch survey_forms
TRUNCATE TABLE `survey_forms`;
INSERT INTO `survey_forms` (`id`, `name`, `description`, `custom_module_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`, `description`, `survey_module_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_survey_templates`;

-- patch survey_questions
TRUNCATE TABLE `survey_questions`;
INSERT INTO `survey_questions` (`id`, `name`, `field_type`, `is_mandatory`, `is_unique`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`,
	CASE
		WHEN `type` = 2 THEN 'TEXT'
	    WHEN `type` = 3 THEN 'DROPDOWN'
	    WHEN `type` = 4 THEN 'CHECKBOX'
	    WHEN `type` = 5 THEN 'TEXTAREA'
	    WHEN `type` = 6 THEN 'NUMBER'
	    WHEN `type` = 7 THEN 'TABLE'
	    ELSE '-1'
	END,
	`is_mandatory`, `is_unique`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_survey_questions`
WHERE `type` != 1;

-- patch survey_question_choices
TRUNCATE TABLE `survey_question_choices`;
INSERT INTO `survey_question_choices` (`id`, `name`, `is_default`, `visible`, `order`, `survey_question_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `value`, `default_option`, `visible`, `order`, `survey_question_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_survey_question_choices`;

-- patch survey_table_columns
TRUNCATE TABLE `survey_table_columns`;
INSERT INTO `survey_table_columns` (`id`, `name`, `order`, `visible`, `survey_question_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`, `order`, `visible`, `survey_question_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_survey_table_columns`;

-- patch survey_table_rows
TRUNCATE TABLE `survey_table_rows`;
INSERT INTO `survey_table_rows` (`id`, `name`, `order`, `visible`, `survey_question_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`, `order`, `visible`, `survey_question_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_survey_table_rows`;

-- patch survey_forms_questions
DELIMITER $$

DROP PROCEDURE IF EXISTS survey_patch
$$
CREATE PROCEDURE survey_patch()
BEGIN
	DECLARE done INT DEFAULT FALSE;
	DECLARE questionId, formId INT(11);
	DECLARE questionOrder INT(3);
	DECLARE questionType INT(1);
	DECLARE questionName VARCHAR(250);
	DECLARE sectionName VARCHAR(250);
	DECLARE sfq CURSOR FOR 
		SELECT `SurveyQuestions`.`id`, `SurveyQuestions`.`name`, `SurveyQuestions`.`type`, `SurveyQuestions`.`order`, `SurveyQuestions`.`survey_template_id`
		FROM `z_1461_survey_questions` AS `SurveyQuestions`
		ORDER BY `SurveyQuestions`.`survey_template_id`, `SurveyQuestions`.`order`;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

	OPEN sfq;
	TRUNCATE TABLE `survey_forms_questions`;

	read_loop: LOOP
	FETCH sfq INTO questionId, questionName, questionType, questionOrder, formId;
	IF done THEN
		LEAVE read_loop;
	END IF;

		IF questionType = 1 THEN
			SET @sectionName = questionName;
		END IF;

		IF questionType <> 1 THEN
			INSERT INTO `survey_forms_questions` (`id`, `survey_form_id`, `survey_question_id`, `section`, `order`) VALUES (uuid(), formId, questionId, @sectionName, questionOrder);
		END IF;

	END LOOP read_loop;

	CLOSE sfq;
END
$$

CALL survey_patch
$$

DROP PROCEDURE IF EXISTS survey_patch
$$

DELIMITER ;

-- patch institution_site_surveys
TRUNCATE TABLE `institution_site_surveys`;
INSERT INTO `institution_site_surveys` (`id`, `status`, `academic_period_id`, `survey_form_id`, `institution_site_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `status`, `academic_period_id`, `survey_template_id`, `institution_site_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_institution_site_surveys`;

-- patch institution_site_survey_answers
TRUNCATE TABLE `institution_site_survey_answers`;
INSERT INTO `institution_site_survey_answers` (`id`, `text_value`, `number_value`, `textarea_value`, `survey_question_id`, `institution_site_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(), `text_value`, `int_value`, `textarea_value`, `survey_question_id`, `institution_site_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_institution_site_survey_answers`;

-- patch institution_site_survey_table_cells
TRUNCATE TABLE `institution_site_survey_table_cells`;
INSERT INTO `institution_site_survey_table_cells` (`id`, `text_value`, `survey_question_id`, `survey_table_column_id`, `survey_table_row_id`, `institution_site_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(), `value`, `survey_question_id`, `survey_table_column_id`, `survey_table_row_id`, `institution_site_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_institution_site_survey_table_cells`;
