-- POCOR-3706
DROP TABLE `system_errors`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3706';


-- POCOR-3563
-- institution_subject_staff
DROP TABLE `institution_subject_staff`;

RENAME TABLE `z_3563_institution_subject_staff` TO `institution_subject_staff`;

-- labels
DELETE FROM `labels` WHERE
`id` IN ('1ebef019-d3df-11e6-907e-525400b263eb', '74436ffe-d63e-11e6-ad42-525400b263eb', '9c0c7533-d63e-11e6-ad42-525400b263eb', 'f94ed6be-d63e-11e6-ad42-525400b263eb');

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3563';


-- POCOR-3737
-- system_patches
UPDATE `security_functions` SET `_edit`= 'StudentSurveys.edit' WHERE `id`='1012';

UPDATE `security_role_functions`
INNER JOIN `z_3737_security_role_functions` ON `z_3737_security_role_functions`.`id` = `security_role_functions`.`id`
SET `security_role_functions`.`_edit` = `z_3737_security_role_functions`.`_edit`;

DROP TABLE `z_3737_security_role_functions`;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3737';


-- POCOR-3748
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


-- 3.8.7
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.8.7' WHERE code = 'db_version';
