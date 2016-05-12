-- POCOR-2968
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2968', NOW());


-- code here
ALTER TABLE `survey_status_periods` ADD INDEX `academic_period_id` (`academic_period_id`);
ALTER TABLE `institution_student_admission` ADD INDEX `academic_period_id` (`academic_period_id`);
ALTER TABLE `institution_student_dropout` ADD INDEX `academic_period_id` (`academic_period_id`);
ALTER TABLE `institution_surveys` ADD INDEX `academic_period_id` (`academic_period_id`);

ALTER TABLE `academic_periods` ADD INDEX `academic_period_level_id` (`academic_period_level_id`);

ALTER TABLE `institutions` ADD INDEX `area_administrative_id` (`area_administrative_id`);
ALTER TABLE `area_administrative_levels` ADD INDEX `area_administrative_id` (`area_administrative_id`);

ALTER TABLE `institution_bank_accounts` ADD INDEX `bank_branch_id` (`bank_branch_id`);

ALTER TABLE `bank_branches` ADD INDEX `bank_id` (`bank_id`);

ALTER TABLE `custom_table_columns` ADD INDEX `custom_field_id` (`custom_field_id`);
ALTER TABLE `custom_forms_fields` ADD INDEX `custom_field_id` (`custom_field_id`);
ALTER TABLE `custom_table_rows` ADD INDEX `custom_field_id` (`custom_field_id`);
ALTER TABLE `custom_field_options` ADD INDEX `custom_field_id` (`custom_field_id`);
ALTER TABLE `custom_table_cells` ADD INDEX `custom_field_id` (`custom_field_id`);

ALTER TABLE `custom_forms_filters` ADD INDEX `custom_filter_id` (`custom_filter_id`);

ALTER TABLE `custom_records` ADD INDEX `custom_form_id` (`custom_form_id`);
ALTER TABLE `custom_forms_fields` ADD INDEX `custom_form_id` (`custom_form_id`);
ALTER TABLE `custom_forms_filters` ADD INDEX `custom_form_id` (`custom_form_id`);

ALTER TABLE `custom_forms` ADD INDEX `custom_module_id` (`custom_module_id`);
ALTER TABLE `survey_forms` ADD INDEX `custom_module_id` (`custom_module_id`);
ALTER TABLE `student_custom_forms` ADD INDEX `custom_module_id` (`custom_module_id`);
ALTER TABLE `staff_custom_forms` ADD INDEX `custom_module_id` (`custom_module_id`);
ALTER TABLE `institution_custom_forms` ADD INDEX `custom_module_id` (`custom_module_id`);
ALTER TABLE `infrastructure_custom_forms` ADD INDEX `custom_module_id` (`custom_module_id`);

ALTER TABLE `custom_table_cells` ADD INDEX `custom_record_id` (`custom_record_id`);
ALTER TABLE `custom_table_cells` ADD INDEX `custom_table_column_id` (`custom_table_column_id`);
ALTER TABLE `custom_table_cells` ADD INDEX `custom_table_row_id` (`custom_table_row_id`);

ALTER TABLE `education_programmes` ADD INDEX `education_certification_id` (`education_certification_id`);
ALTER TABLE `education_programmes` ADD INDEX `education_cycle_id` (`education_cycle_id`);
ALTER TABLE `education_programmes` ADD INDEX `education_field_of_study_id` (`education_field_of_study_id`);

ALTER TABLE `institution_student_admission` ADD INDEX `education_grade_id` (`education_grade_id`);
ALTER TABLE `institution_grades` ADD INDEX `education_grade_id` (`education_grade_id`);
ALTER TABLE `institution_student_dropout` ADD INDEX `education_grade_id` (`education_grade_id`);

ALTER TABLE `education_levels` ADD INDEX `education_level_isced_id` (`education_level_isced_id`);
ALTER TABLE `education_field_of_studies` ADD INDEX `education_programme_orientation_id` (`education_programme_orientation_id`);
ALTER TABLE `education_levels` ADD INDEX `education_system_id` (`education_system_id`);
ALTER TABLE `workflows_filters` ADD INDEX `filter_id` (`filter_id`);
ALTER TABLE `countries` ADD INDEX `identity_type_id` (`identity_type_id`);

ALTER TABLE `infrastructure_custom_table_rows` ADD INDEX `infrastructure_custom_field_id` (`infrastructure_custom_field_id`);
ALTER TABLE `infrastructure_custom_table_cells` ADD INDEX `infrastructure_custom_field_id` (`infrastructure_custom_field_id`);
ALTER TABLE `infrastructure_custom_table_columns` ADD INDEX `infrastructure_custom_field_id` (`infrastructure_custom_field_id`);
ALTER TABLE `infrastructure_custom_forms_fields` ADD INDEX `infrastructure_custom_field_id` (`infrastructure_custom_field_id`);

ALTER TABLE `infrastructure_custom_forms_filters` ADD INDEX `infrastructure_custom_filter_id` (`infrastructure_custom_filter_id`);
ALTER TABLE `infrastructure_custom_forms_fields` ADD INDEX `infrastructure_custom_form_id` (`infrastructure_custom_form_id`);
ALTER TABLE `infrastructure_custom_forms_filters` ADD INDEX `infrastructure_custom_form_id` (`infrastructure_custom_form_id`);
ALTER TABLE `infrastructure_custom_table_cells` ADD INDEX `infrastructure_custom_table_column_id` (`infrastructure_custom_table_column_id`);
ALTER TABLE `infrastructure_custom_table_cells` ADD INDEX `infrastructure_custom_table_row_id` (`infrastructure_custom_table_row_id`);

ALTER TABLE `institution_custom_table_columns` ADD INDEX `institution_custom_field_id` (`institution_custom_field_id`);
ALTER TABLE `institution_custom_field_options` ADD INDEX `institution_custom_field_id` (`institution_custom_field_id`);
ALTER TABLE `institution_custom_table_cells` ADD INDEX `institution_custom_field_id` (`institution_custom_field_id`);
ALTER TABLE `institution_custom_table_rows` ADD INDEX `institution_custom_field_id` (`institution_custom_field_id`);
ALTER TABLE `institution_custom_forms_fields` ADD INDEX `institution_custom_field_id` (`institution_custom_field_id`);

ALTER TABLE `institution_custom_forms_filters` ADD INDEX `institution_custom_filter_id` (`institution_custom_filter_id`);
ALTER TABLE `institution_custom_forms_fields` ADD INDEX `institution_custom_form_id` (`institution_custom_form_id`);
ALTER TABLE `institution_custom_forms_filters` ADD INDEX `institution_custom_form_id` (`institution_custom_form_id`);
ALTER TABLE `institution_custom_table_cells` ADD INDEX `institution_custom_table_column_id` (`institution_custom_table_column_id`);
ALTER TABLE `institution_custom_table_cells` ADD INDEX `institution_custom_table_row_id` (`institution_custom_table_row_id`);

ALTER TABLE `institution_student_dropout` ADD INDEX `institution_id` (`institution_id`);
ALTER TABLE `institution_bank_accounts` ADD INDEX `institution_id` (`institution_id`);
ALTER TABLE `institution_student_absences` ADD INDEX `institution_id` (`institution_id`);
ALTER TABLE `institution_student_admission` ADD INDEX `institution_id` (`institution_id`);

ALTER TABLE `infrastructure_custom_table_cells` ADD INDEX `institution_infrastructure_id` (`institution_infrastructure_id`);
ALTER TABLE `institution_staff_position_profiles` ADD INDEX `institution_staff_id` (`institution_staff_id`);
ALTER TABLE `institution_survey_table_cells` ADD INDEX `institution_survey_id` (`institution_survey_id`);
ALTER TABLE `institution_student_surveys` ADD INDEX `parent_form_id` (`parent_form_id`);

ALTER TABLE `security_functions` ADD INDEX `parent_id` (`parent_id`);
ALTER TABLE `institution_infrastructures` ADD INDEX `parent_id` (`parent_id`);
ALTER TABLE `infrastructure_levels` ADD INDEX `parent_id` (`parent_id`);
ALTER TABLE `custom_modules` ADD INDEX `parent_id` (`parent_id`);

ALTER TABLE `institution_student_admission` ADD INDEX `previous_institution_id` (`previous_institution_id`);
ALTER TABLE `staff_qualifications` ADD INDEX `qualification_level_id` (`qualification_level_id`);
ALTER TABLE `batch_processes` ADD INDEX `reference_id` (`reference_id`);
ALTER TABLE `security_role_functions` ADD INDEX `security_function_id` (`security_function_id`);
ALTER TABLE `security_role_functions` ADD INDEX `security_role_id` (`security_role_id`);
ALTER TABLE `workflow_steps_roles` ADD INDEX `security_role_id` (`security_role_id`);
ALTER TABLE `institution_staff_position_profiles` ADD INDEX `staff_change_type_id` (`staff_change_type_id`);

ALTER TABLE `staff_custom_table_cells` ADD INDEX `staff_custom_field_id` (`staff_custom_field_id`);
ALTER TABLE `staff_custom_table_columns` ADD INDEX `staff_custom_field_id` (`staff_custom_field_id`);
ALTER TABLE `staff_custom_forms_fields` ADD INDEX `staff_custom_field_id` (`staff_custom_field_id`);
ALTER TABLE `staff_custom_table_rows` ADD INDEX `staff_custom_field_id` (`staff_custom_field_id`);

ALTER TABLE `staff_custom_forms_fields` ADD INDEX `staff_custom_form_id` (`staff_custom_form_id`);
ALTER TABLE `staff_custom_table_cells` ADD INDEX `staff_custom_table_column_id` (`staff_custom_table_column_id`);
ALTER TABLE `staff_custom_table_cells` ADD INDEX `staff_custom_table_row_id` (`staff_custom_table_row_id`);
ALTER TABLE `staff_custom_table_cells` ADD INDEX `staff_id` (`staff_id`);

ALTER TABLE `institution_positions` ADD INDEX `staff_position_grade_id` (`staff_position_grade_id`);
ALTER TABLE `institution_positions` ADD INDEX `staff_position_title_id` (`staff_position_title_id`);
ALTER TABLE `staff_trainings` ADD INDEX `staff_training_category_id` (`staff_training_category_id`);
ALTER TABLE `institution_staff_assignments` ADD INDEX `staff_type_id` (`staff_type_id`);

ALTER TABLE `institution_positions` ADD INDEX `status_id` (`status_id`);
ALTER TABLE `institution_student_surveys` ADD INDEX `status_id` (`status_id`);
ALTER TABLE `institution_staff_position_profiles` ADD INDEX `status_id` (`status_id`);
ALTER TABLE `institution_surveys` ADD INDEX `status_id` (`status_id`);

ALTER TABLE `student_custom_table_cells` ADD INDEX `student_custom_field_id` (`student_custom_field_id`);
ALTER TABLE `student_custom_table_columns` ADD INDEX `student_custom_field_id` (`student_custom_field_id`);
ALTER TABLE `student_custom_forms_fields` ADD INDEX `student_custom_field_id` (`student_custom_field_id`);
ALTER TABLE `student_custom_table_rows` ADD INDEX `student_custom_field_id` (`student_custom_field_id`);

ALTER TABLE `student_custom_forms_fields` ADD INDEX `student_custom_form_id` (`student_custom_form_id`);
ALTER TABLE `student_custom_table_cells` ADD INDEX `student_custom_table_column_id` (`student_custom_table_column_id`);
ALTER TABLE `student_custom_table_cells` ADD INDEX `student_custom_table_row_id` (`student_custom_table_row_id`);
ALTER TABLE `institution_student_dropout` ADD INDEX `student_dropout_reason_id` (`student_dropout_reason_id`);

ALTER TABLE `institution_student_admission` ADD INDEX `student_id` (`student_id`);
ALTER TABLE `institution_student_dropout` ADD INDEX `student_id` (`student_id`);
ALTER TABLE `student_custom_table_cells` ADD INDEX `student_id` (`student_id`);

ALTER TABLE `institution_student_admission` ADD INDEX `student_transfer_reason_id` (`student_transfer_reason_id`);

ALTER TABLE `institution_surveys` ADD INDEX `survey_form_id` (`survey_form_id`);
ALTER TABLE `survey_statuses` ADD INDEX `survey_form_id` (`survey_form_id`);
ALTER TABLE `survey_forms_questions` ADD INDEX `survey_form_id` (`survey_form_id`);

ALTER TABLE `survey_table_columns` ADD INDEX `survey_question_id` (`survey_question_id`);
ALTER TABLE `institution_survey_table_cells` ADD INDEX `survey_question_id` (`survey_question_id`);
ALTER TABLE `survey_forms_questions` ADD INDEX `survey_question_id` (`survey_question_id`);
ALTER TABLE `survey_table_rows` ADD INDEX `survey_question_id` (`survey_question_id`);
ALTER TABLE `survey_question_choices` ADD INDEX `survey_question_id` (`survey_question_id`);

ALTER TABLE `survey_status_periods` ADD INDEX `survey_status_id` (`survey_status_id`);

ALTER TABLE `institution_survey_table_cells` ADD INDEX `survey_table_column_id` (`survey_table_column_id`);
ALTER TABLE `institution_survey_table_cells` ADD INDEX `survey_table_row_id` (`survey_table_row_id`);

ALTER TABLE `workflows_filters` ADD INDEX `workflow_id` (`workflow_id`);
ALTER TABLE `workflow_statuses` ADD INDEX `workflow_model_id` (`workflow_model_id`);
ALTER TABLE `workflow_comments` ADD INDEX `workflow_record_id` (`workflow_record_id`);
ALTER TABLE `workflow_statuses_steps` ADD INDEX `workflow_status_id` (`workflow_status_id`);
ALTER TABLE `workflow_steps_roles` ADD INDEX `workflow_step_id` (`workflow_step_id`);
ALTER TABLE `workflow_statuses_steps` ADD INDEX `workflow_step_id` (`workflow_step_id`);

ALTER TABLE `security_users` ADD INDEX `super_admin` (`super_admin`);

ALTER TABLE `education_grades_subjects` ADD INDEX `education_grade_id` (`education_grade_id`);
ALTER TABLE `education_cycles` ADD INDEX `education_level_id` (`education_level_id`);
ALTER TABLE `education_grades_subjects` ADD INDEX `education_subject_id` (`education_subject_id`);
ALTER TABLE `infrastructure_custom_field_options` ADD INDEX `infrastructure_custom_field_id` (`infrastructure_custom_field_id`);
ALTER TABLE `report_progress` ADD INDEX `pid` (`pid`);
ALTER TABLE `system_processes` ADD INDEX `process_id` (`process_id`);
ALTER TABLE `staff_custom_field_options` ADD INDEX `staff_custom_field_id` (`staff_custom_field_id`);
ALTER TABLE `student_custom_field_options` ADD INDEX `student_custom_field_id` (`student_custom_field_id`);


-- POCOR-2614
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2614', NOW());

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Next grade in the Education Structure is not available in this Institution.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Next grade in the Education Structure is not available in this Institution.');

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'No Available Grades in this Institution', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'No Available Grades in this Institution');

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'No Available Academic Periods', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'No Available Academic Periods');


-- POCOR-2899
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2899', NOW());

-- remove orphan / test record
DELETE FROM `institution_staff_position_profiles`
WHERE NOT EXISTS (
        SELECT 1 FROM `institutions`
                WHERE `institutions`.`id` = `institution_staff_position_profiles`.`institution_id`
);


-- POCOR-2885
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2885', NOW());

-- new table

CREATE TABLE IF NOT EXISTS `special_need_difficulties` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 COLLATE utf8mb4_unicode_ci;

INSERT INTO `special_need_difficulties` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'None', 1, 1, 1, 1, '', '', NULL, NULL, 1, NOW()),
(2, 'Some', 2, 1, 1, 0, '', '', NULL, NULL, 1, NOW()),
(3, 'A Lot', 3, 1, 1, 0, '', '', NULL, NULL, 1, NOW()),
(4, 'Unable', 4, 1, 1, 0, '', '', NULL, NULL, 1, NOW());

--
ALTER TABLE `special_need_difficulties`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `special_need_difficulties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;


-- backup the old table

ALTER TABLE `user_special_needs`
RENAME TO  `z_2885_user_special_needs` ;

-- create new table and apply the changess

CREATE TABLE IF NOT EXISTS `user_special_needs` (
  `id` int(11) NOT NULL,
  `special_need_date` date NOT NULL,
  `comment` text,
  `security_user_id` int(11) NOT NULL,
  `special_need_type_id` int(11) NOT NULL,
  `special_need_difficulty_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
)ENGINE=InnoDB COLLATE utf8mb4_unicode_ci;

ALTER TABLE `user_special_needs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `special_need_type_id` (`special_need_type_id`),
  ADD KEY `special_need_difficulty_id` (`special_need_difficulty_id`),
  ADD KEY `security_user_id` (`security_user_id`);

ALTER TABLE `user_special_needs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- reinsert from backup table with value changes

INSERT INTO user_special_needs
SELECT `z_2885_user_special_needs`.`id`,
  `z_2885_user_special_needs`.`special_need_date`,
  `z_2885_user_special_needs`.`comment`,
  `z_2885_user_special_needs`.`security_user_id`,
  `z_2885_user_special_needs`.`special_need_type_id`,
  1,
  `z_2885_user_special_needs`.`modified_user_id`,
  `z_2885_user_special_needs`.`modified`,
  `z_2885_user_special_needs`.`created_user_id`,
  `z_2885_user_special_needs`.`created`
FROM `z_2885_user_special_needs`;

-- update the order column and insert new record with desired order

UPDATE field_options
SET `order` = `order`+1
WHERE `order` >= 49;

INSERT INTO `field_options` (`id`, `plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES (NULL, 'FieldOption', 'SpecialNeedDifficulties', 'Special Need Difficulties', 'Others', '{"model":"FieldOption.SpecialNeedDifficulties"}', '49', '1', NULL, NULL, '1', NOW());

-- add label
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `created_user_id`, `created`) VALUES
(uuid(), 'SpecialNeeds', 'special_need_difficulty_id', 'Special Needs', 'Difficulty', NULL, NULL, 1, 0, NOW());


-- 3.5.5
UPDATE config_items SET value = '3.5.5' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
