-- code here
ALTER TABLE `survey_status_periods` DROP INDEX `academic_period_id`;
ALTER TABLE `institution_student_admission` DROP INDEX `academic_period_id`;
ALTER TABLE `institution_student_dropout` DROP INDEX `academic_period_id`;
ALTER TABLE `institution_surveys` DROP INDEX `academic_period_id`;
ALTER TABLE `academic_periods` DROP INDEX `academic_period_level_id`;

ALTER TABLE `institutions` DROP INDEX `area_administrative_id`;
ALTER TABLE `area_administrative_levels` DROP INDEX `area_administrative_id`;
	
ALTER TABLE `institution_bank_accounts` DROP INDEX `bank_branch_id`;

ALTER TABLE `bank_branches` DROP INDEX `bank_id`;

ALTER TABLE `custom_table_columns` DROP INDEX `custom_field_id`;
ALTER TABLE `custom_forms_fields` DROP INDEX `custom_field_id`;
ALTER TABLE `custom_table_rows` DROP INDEX `custom_field_id`;
ALTER TABLE `custom_field_options` DROP INDEX `custom_field_id`;
ALTER TABLE `custom_table_cells` DROP INDEX `custom_field_id`;
	
ALTER TABLE `custom_forms_filters` DROP INDEX `custom_filter_id`;

ALTER TABLE `custom_records` DROP INDEX `custom_form_id`;
ALTER TABLE `custom_forms_fields` DROP INDEX `custom_form_id`;
ALTER TABLE `custom_forms_filters` DROP INDEX `custom_form_id`;

ALTER TABLE `custom_forms` DROP INDEX `custom_module_id`;
ALTER TABLE `survey_forms` DROP INDEX `custom_module_id`;
ALTER TABLE `student_custom_forms` DROP INDEX `custom_module_id`;
ALTER TABLE `staff_custom_forms` DROP INDEX `custom_module_id`;
ALTER TABLE `institution_custom_forms` DROP INDEX `custom_module_id`;
ALTER TABLE `infrastructure_custom_forms` DROP INDEX `custom_module_id`;

ALTER TABLE `custom_table_cells` DROP INDEX `custom_record_id`;
ALTER TABLE `custom_table_cells` DROP INDEX `custom_table_column_id`;
ALTER TABLE `custom_table_cells` DROP INDEX `custom_table_row_id`;

ALTER TABLE `education_programmes` DROP INDEX `education_certification_id`;
ALTER TABLE `education_programmes` DROP INDEX `education_cycle_id`;
ALTER TABLE `education_programmes` DROP INDEX `education_field_of_study_id`;

ALTER TABLE `institution_student_admission` DROP INDEX `education_grade_id`;
ALTER TABLE `institution_grades` DROP INDEX `education_grade_id`;
ALTER TABLE `institution_student_dropout` DROP INDEX `education_grade_id`;

ALTER TABLE `education_levels` DROP INDEX `education_level_isced_id`;
ALTER TABLE `education_field_of_studies` DROP INDEX `education_programme_orientation_id`;
ALTER TABLE `education_levels` DROP INDEX `education_system_id`;
ALTER TABLE `workflows_filters` DROP INDEX `filter_id`;
ALTER TABLE `countries` DROP INDEX `identity_type_id`;

ALTER TABLE `infrastructure_custom_table_rows` DROP INDEX `infrastructure_custom_field_id`;
ALTER TABLE `infrastructure_custom_table_cells` DROP INDEX `infrastructure_custom_field_id`;
ALTER TABLE `infrastructure_custom_table_columns` DROP INDEX `infrastructure_custom_field_id`;
ALTER TABLE `infrastructure_custom_forms_fields` DROP INDEX `infrastructure_custom_field_id`;

ALTER TABLE `infrastructure_custom_forms_filters` DROP INDEX `infrastructure_custom_filter_id`;
ALTER TABLE `infrastructure_custom_forms_fields` DROP INDEX `infrastructure_custom_form_id`;
ALTER TABLE `infrastructure_custom_forms_filters` DROP INDEX `infrastructure_custom_form_id`;
ALTER TABLE `infrastructure_custom_table_cells` DROP INDEX `infrastructure_custom_table_column_id`;
ALTER TABLE `infrastructure_custom_table_cells` DROP INDEX `infrastructure_custom_table_row_id`;
	
ALTER TABLE `institution_custom_table_columns` DROP INDEX `institution_custom_field_id`;
ALTER TABLE `institution_custom_field_options` DROP INDEX `institution_custom_field_id`;
ALTER TABLE `institution_custom_table_cells` DROP INDEX `institution_custom_field_id`;
ALTER TABLE `institution_custom_table_rows` DROP INDEX `institution_custom_field_id`;
ALTER TABLE `institution_custom_forms_fields` DROP INDEX `institution_custom_field_id`;
	
ALTER TABLE `institution_custom_forms_filters` DROP INDEX `institution_custom_filter_id`;
ALTER TABLE `institution_custom_forms_fields` DROP INDEX `institution_custom_form_id`;
ALTER TABLE `institution_custom_forms_filters` DROP INDEX `institution_custom_form_id`;
ALTER TABLE `institution_custom_table_cells` DROP INDEX `institution_custom_table_column_id`;
ALTER TABLE `institution_custom_table_cells` DROP INDEX `institution_custom_table_row_id`;
	
ALTER TABLE `institution_student_dropout` DROP INDEX `institution_id`;
ALTER TABLE `institution_bank_accounts` DROP INDEX `institution_id`;
ALTER TABLE `institution_student_absences` DROP INDEX `institution_id`;
ALTER TABLE `institution_student_admission` DROP INDEX `institution_id`;
	
ALTER TABLE `infrastructure_custom_table_cells` DROP INDEX `institution_infrastructure_id`;
ALTER TABLE `institution_staff_position_profiles` DROP INDEX `institution_staff_id`;
ALTER TABLE `institution_survey_table_cells` DROP INDEX `institution_survey_id`;
ALTER TABLE `institution_student_surveys` DROP INDEX `parent_form_id`;
	
ALTER TABLE `security_functions` DROP INDEX `parent_id`;
ALTER TABLE `institution_infrastructures` DROP INDEX `parent_id`;
ALTER TABLE `infrastructure_levels` DROP INDEX `parent_id`;
ALTER TABLE `custom_modules` DROP INDEX `parent_id`;
	
ALTER TABLE `institution_student_admission` DROP INDEX `previous_institution_id`;
ALTER TABLE `staff_qualifications` DROP INDEX `qualification_level_id`;
ALTER TABLE `batch_processes` DROP INDEX `reference_id`;
ALTER TABLE `security_role_functions` DROP INDEX `security_function_id`;
ALTER TABLE `security_role_functions` DROP INDEX `security_role_id`;
ALTER TABLE `workflow_steps_roles` DROP INDEX `security_role_id`;
ALTER TABLE `institution_staff_position_profiles` DROP INDEX `staff_change_type_id`;

ALTER TABLE `staff_custom_table_cells` DROP INDEX `staff_custom_field_id`;
ALTER TABLE `staff_custom_table_columns` DROP INDEX `staff_custom_field_id`;
ALTER TABLE `staff_custom_forms_fields` DROP INDEX `staff_custom_field_id`;
ALTER TABLE `staff_custom_table_rows` DROP INDEX `staff_custom_field_id`;

ALTER TABLE `staff_custom_forms_fields` DROP INDEX `staff_custom_form_id`;
ALTER TABLE `staff_custom_table_cells` DROP INDEX `staff_custom_table_column_id`;
ALTER TABLE `staff_custom_table_cells` DROP INDEX `staff_custom_table_row_id`;
ALTER TABLE `staff_custom_table_cells` DROP INDEX `staff_id`;

ALTER TABLE `institution_positions` DROP INDEX `staff_position_grade_id`;
ALTER TABLE `institution_positions` DROP INDEX `staff_position_title_id`;
ALTER TABLE `staff_trainings` DROP INDEX `staff_training_category_id`;
ALTER TABLE `institution_staff_assignments` DROP INDEX `staff_type_id`;

ALTER TABLE `institution_positions` DROP INDEX `status_id`;
ALTER TABLE `institution_student_surveys` DROP INDEX `status_id`;
ALTER TABLE `institution_staff_position_profiles` DROP INDEX `status_id`;
ALTER TABLE `institution_surveys` DROP INDEX `status_id`;

ALTER TABLE `student_custom_table_cells` DROP INDEX `student_custom_field_id`;
ALTER TABLE `student_custom_table_columns` DROP INDEX `student_custom_field_id`;
ALTER TABLE `student_custom_forms_fields` DROP INDEX `student_custom_field_id`;
ALTER TABLE `student_custom_table_rows` DROP INDEX `student_custom_field_id`;
	
ALTER TABLE `student_custom_forms_fields` DROP INDEX `student_custom_form_id`;
ALTER TABLE `student_custom_table_cells` DROP INDEX `student_custom_table_column_id`;
ALTER TABLE `student_custom_table_cells` DROP INDEX `student_custom_table_row_id`;
ALTER TABLE `institution_student_dropout` DROP INDEX `student_dropout_reason_id`;
	
ALTER TABLE `institution_student_admission` DROP INDEX `student_id`;
ALTER TABLE `institution_student_dropout` DROP INDEX `student_id`;
ALTER TABLE `student_custom_table_cells` DROP INDEX `student_id`;
	
ALTER TABLE `institution_student_admission` DROP INDEX `student_transfer_reason_id`;
	
ALTER TABLE `institution_surveys` DROP INDEX `survey_form_id`;
ALTER TABLE `survey_statuses` DROP INDEX `survey_form_id`;
ALTER TABLE `survey_forms_questions` DROP INDEX `survey_form_id`;
	
ALTER TABLE `survey_table_columns` DROP INDEX `survey_question_id`;
ALTER TABLE `institution_survey_table_cells` DROP INDEX `survey_question_id`;
ALTER TABLE `survey_forms_questions` DROP INDEX `survey_question_id`;
ALTER TABLE `survey_table_rows` DROP INDEX `survey_question_id`;
ALTER TABLE `survey_question_choices` DROP INDEX `survey_question_id`;

ALTER TABLE `survey_status_periods` DROP INDEX `survey_status_id`;

ALTER TABLE `institution_survey_table_cells` DROP INDEX `survey_table_column_id`;
ALTER TABLE `institution_survey_table_cells` DROP INDEX `survey_table_row_id`;

ALTER TABLE `workflows_filters` DROP INDEX `workflow_id`;
ALTER TABLE `workflow_statuses` DROP INDEX `workflow_model_id`;
ALTER TABLE `workflow_comments` DROP INDEX `workflow_record_id`;
ALTER TABLE `workflow_statuses_steps` DROP INDEX `workflow_status_id`;
ALTER TABLE `workflow_steps_roles` DROP INDEX `workflow_step_id`;
ALTER TABLE `workflow_statuses_steps` DROP INDEX `workflow_step_id`;
	
ALTER TABLE `education_grades_subjects` DROP INDEX `education_grade_id`;
ALTER TABLE `education_cycles` DROP INDEX `education_level_id`;
ALTER TABLE `education_grades_subjects` DROP INDEX `education_subject_id`;
ALTER TABLE `infrastructure_custom_field_options` DROP INDEX `infrastructure_custom_field_id`;
ALTER TABLE `report_progress` DROP INDEX `pid`;
ALTER TABLE `system_processes` DROP INDEX `process_id`;
ALTER TABLE `staff_custom_field_options` DROP INDEX `staff_custom_field_id`;
ALTER TABLE `student_custom_field_options` DROP INDEX `student_custom_field_id`;

ALTER TABLE `security_users` DROP INDEX `super_admin`;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2968';