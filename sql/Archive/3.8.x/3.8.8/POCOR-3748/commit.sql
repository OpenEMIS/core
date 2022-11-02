-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3748', NOW());

-- code here
ALTER TABLE `institution_staff_leave`           CHANGE `staff_leave_type_id` `staff_leave_type_id` INT(11) NOT NULL COMMENT 'links to staff_leave_types.id';
ALTER TABLE `institution_student_admission`     CHANGE `student_transfer_reason_id` `student_transfer_reason_id` INT(11) NOT NULL COMMENT 'links to student_transfer_reasons.id';
ALTER TABLE `institution_student_dropout`       CHANGE `student_dropout_reason_id` `student_dropout_reason_id` INT(11) NOT NULL COMMENT 'links to student_dropout_reasons.id';
ALTER TABLE `staff_behaviours`                  CHANGE `staff_behaviour_category_id` `staff_behaviour_category_id` INT(11) NOT NULL COMMENT 'links to staff_behaviour_categories.id';
ALTER TABLE `staff_salary_additions`            CHANGE `salary_addition_type_id` `salary_addition_type_id` INT(11) NOT NULL COMMENT 'links to salary_addition_types.id';
ALTER TABLE `staff_salary_deductions`           CHANGE `salary_deduction_type_id` `salary_deduction_type_id` INT(11) NOT NULL COMMENT 'links to salary_deduction_types.id';
ALTER TABLE `staff_training_needs`              CHANGE `training_need_category_id` `training_need_category_id` INT(11) NOT NULL COMMENT 'links to training_need_categories.id';
ALTER TABLE `staff_training_needs`              CHANGE `training_requirement_id` `training_requirement_id` INT(11) NOT NULL COMMENT 'links to training_requirements.id';
ALTER TABLE `staff_training_needs`              CHANGE `training_priority_id` `training_priority_id` INT(11) NOT NULL COMMENT 'links to training_priorities.id';
ALTER TABLE `staff_training_self_studies`       CHANGE `training_achievement_type_id` `training_achievement_type_id` INT(11) NOT NULL COMMENT 'links to training_achievement_types.id';
ALTER TABLE `staff_trainings`                   CHANGE `staff_training_category_id` `staff_training_category_id` INT(11) NOT NULL COMMENT 'links to staff_training_categories.id';
ALTER TABLE `student_behaviours`                CHANGE `student_behaviour_category_id` `student_behaviour_category_id` INT(11) NOT NULL COMMENT 'links to student_behaviour_categories.id';
ALTER TABLE `training_courses`                  CHANGE `training_field_of_study_id` `training_field_of_study_id` INT(11) NOT NULL COMMENT 'links to training_field_of_studies.id';
ALTER TABLE `training_courses`                  CHANGE `training_course_type_id` `training_course_type_id` INT(11) NOT NULL COMMENT 'links to training_course_types.id';
ALTER TABLE `training_courses`                  CHANGE `training_mode_of_delivery_id` `training_mode_of_delivery_id` INT(11) NOT NULL COMMENT 'links to training_mode_deliveries.id';
ALTER TABLE `training_courses`                  CHANGE `training_requirement_id` `training_requirement_id` INT(11) NOT NULL COMMENT 'links to training_requirements.id';
ALTER TABLE `training_courses`                  CHANGE `training_level_id` `training_level_id` INT(11) NOT NULL COMMENT 'links to training_levels.id';
ALTER TABLE `training_courses_providers`        CHANGE `training_provider_id` `training_provider_id` INT(11) NOT NULL COMMENT 'links to training_providers.id';
ALTER TABLE `training_courses_result_types`     CHANGE `training_result_type_id` `training_result_type_id` INT(11) NOT NULL COMMENT 'links to training_result_types.id';
ALTER TABLE `training_courses_specialisations`  CHANGE `training_specialisation_id` `training_specialisation_id` INT(11) NOT NULL COMMENT 'links to training_specialisations.id';
ALTER TABLE `training_session_trainee_results`  CHANGE `training_result_type_id` `training_result_type_id` INT(11) NOT NULL COMMENT 'links to training_result_types.id';
ALTER TABLE `training_sessions`                 CHANGE `training_provider_id` `training_provider_id` INT(11) NOT NULL COMMENT 'links to training_providers.id';
