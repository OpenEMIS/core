-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1346');

-- New table - institution_student_surveys
DROP TABLE IF EXISTS `institution_student_surveys`;
CREATE TABLE IF NOT EXISTS `institution_student_surveys` (
  `id` int(11) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Draft, 2 -> Completed',
  `institution_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `academic_period_id` int(11) NOT NULL,
  `survey_form_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_student_surveys`
  ADD PRIMARY KEY (`id`), ADD KEY `institution_id` (`institution_id`), ADD KEY `student_id` (`student_id`), ADD KEY `survey_form_id` (`survey_form_id`), ADD KEY `academic_period_id` (`academic_period_id`);


ALTER TABLE `institution_student_surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - institution_student_survey_answers
DROP TABLE IF EXISTS `institution_student_survey_answers`;
CREATE TABLE IF NOT EXISTS `institution_student_survey_answers` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `survey_question_id` int(11) NOT NULL,
  `institution_student_survey_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_student_survey_answers`
  ADD PRIMARY KEY (`id`), ADD KEY `survey_question_id` (`survey_question_id`), ADD KEY `institution_student_survey_id` (`institution_student_survey_id`);

-- New table - institution_student_survey_table_cells
DROP TABLE IF EXISTS `institution_student_survey_table_cells`;
CREATE TABLE IF NOT EXISTS `institution_student_survey_table_cells` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `survey_question_id` int(11) NOT NULL,
  `survey_table_column_id` int(11) NOT NULL,
  `survey_table_row_id` int(11) NOT NULL,
  `institution_student_survey_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_student_survey_table_cells`
  ADD PRIMARY KEY (`id`), ADD KEY `survey_question_id` (`survey_question_id`), ADD KEY `survey_table_column_id` (`survey_table_column_id`), ADD KEY `survey_table_row_id` (`survey_table_row_id`), ADD KEY `institution_student_survey_id` (`institution_student_survey_id`);

-- custom_field_types
INSERT INTO `custom_field_types` (`code`, `name`, `value`, `description`, `format`, `is_mandatory`, `is_unique`, `visible`) VALUES
('STUDENT_LIST', 'Student List', 'text_value', '', 'OpenEMIS_Institution', 0, 0, 1);
