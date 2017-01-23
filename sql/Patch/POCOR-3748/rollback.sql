ALTER TABLE `institution_staff_leave`           CHANGE `staff_leave_type_id` `staff_leave_type_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `institution_student_admission`     CHANGE `student_transfer_reason_id` `student_transfer_reason_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `institution_student_dropout`       CHANGE `student_dropout_reason_id` `student_dropout_reason_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `staff_behaviours`                  CHANGE `staff_behaviour_category_id` `staff_behaviour_category_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `staff_salary_additions`            CHANGE `salary_addition_type_id` `salary_addition_type_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `staff_salary_deductions`           CHANGE `salary_deduction_type_id` `salary_deduction_type_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `staff_training_needs`              CHANGE `training_need_category_id` `training_need_category_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `staff_training_needs`              CHANGE `training_requirement_id` `training_requirement_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `staff_training_needs`              CHANGE `training_priority_id` `training_priority_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `staff_training_self_studies`       CHANGE `training_achievement_type_id` `training_achievement_type_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `staff_trainings`                   CHANGE `staff_training_category_id` `staff_training_category_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `student_behaviours`                CHANGE `student_behaviour_category_id` `student_behaviour_category_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses`                  CHANGE `training_field_of_study_id` `training_field_of_study_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses`                  CHANGE `training_course_type_id` `training_course_type_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses`                  CHANGE `training_mode_of_delivery_id` `training_mode_of_delivery_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses`                  CHANGE `training_requirement_id` `training_requirement_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses`                  CHANGE `training_level_id` `training_level_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses_providers`        CHANGE `training_provider_id` `training_provider_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses_result_types`     CHANGE `training_result_type_id` `training_result_type_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses_specialisations`  CHANGE `training_specialisation_id` `training_specialisation_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_session_trainee_results`  CHANGE `training_result_type_id` `training_result_type_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_sessions`                 CHANGE `training_provider_id` `training_provider_id` INT(11) NOT NULL COMMENT 'links to field_option_values.id';


-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3748';
