-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2451', NOW());

-- custom_field_types
INSERT INTO `custom_field_types` (`code`, `name`, `value`, `description`, `format`, `is_mandatory`, `is_unique`, `visible`) VALUES
('REPEATER', 'Repeater', 'text_value', '', 'OpenEMIS_Institution', 0, 0, 1);

-- custom_modules
UPDATE `custom_modules` SET `supported_field_types` = CONCAT(`supported_field_types`, ',REPEATER') WHERE `model` = 'Institution.Institutions';

INSERT INTO `custom_modules` (`id`, `code`, `name`, `model`, `behavior`, `filter`, `supported_field_types`, `visible`, `parent_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(6, 'Institution > Repeater', 'Institution > Repeater > Survey', 'InstitutionRepeater.RepeaterSurveys', NULL, NULL, 'TEXT,NUMBER,DROPDOWN', 1, 0, NULL, NULL, 1, '0000-00-00 00:00:00');

-- institution_repeater_surveys
DROP TABLE IF EXISTS `institution_repeater_surveys`;
CREATE TABLE IF NOT EXISTS `institution_repeater_surveys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `institution_id` int(11) NOT NULL,
  `repeater_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `survey_form_id` int(11) NOT NULL,
  `parent_form_id` int(11) NOT NULL COMMENT 'links to institution_surveys.survey_form_id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `status_id` (`status_id`),
  INDEX `institution_id` (`institution_id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `survey_form_id` (`survey_form_id`),
  INDEX `parent_form_id` (`parent_form_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- institution_repeater_survey_answers
DROP TABLE IF EXISTS `institution_repeater_survey_answers`;
CREATE TABLE IF NOT EXISTS `institution_repeater_survey_answers` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `survey_question_id` int(11) NOT NULL,
  `institution_repeater_survey_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `survey_question_id` (`survey_question_id`),
  INDEX `institution_repeater_survey_id` (`institution_repeater_survey_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- institution_repeater_survey_table_cells
DROP TABLE IF EXISTS `institution_repeater_survey_table_cells`;
CREATE TABLE IF NOT EXISTS `institution_repeater_survey_table_cells` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `survey_question_id` int(11) NOT NULL,
  `survey_table_column_id` int(11) NOT NULL,
  `survey_table_row_id` int(11) NOT NULL,
  `institution_repeater_survey_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `survey_question_id` (`survey_question_id`),
  INDEX `survey_table_column_id` (`survey_table_column_id`),
  INDEX `survey_table_row_id` (`survey_table_row_id`),
  INDEX `institution_repeater_survey_id` (`institution_repeater_survey_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
