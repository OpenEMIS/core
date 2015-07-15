-- 14th July 2015

ALTER TABLE `custom_field_types` ADD `visible` INT(1) NOT NULL DEFAULT '1' AFTER `is_unique`;
UPDATE `custom_field_types` SET `visible` = 0 WHERE `code` IN ('CHECKBOX', 'DATE', 'TIME');

ALTER TABLE `survey_form_questions` ADD `section` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `survey_question_id`;
ALTER TABLE `survey_form_questions` CHANGE `name` `name` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

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
	    WHEN `type` = 4 THEN 'DROPDOWN'
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

-- patch survey_form_questions
TRUNCATE TABLE `survey_form_questions`;
INSERT INTO `survey_form_questions` (`id`, `survey_form_id`, `survey_question_id`, `order`)
SELECT uuid(), `survey_template_id`, `id`, `order`
FROM `z_1461_survey_questions`;

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
