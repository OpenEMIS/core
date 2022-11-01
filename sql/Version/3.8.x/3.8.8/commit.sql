-- POCOR-3706
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3706', NOW());

CREATE TABLE `system_errors` (
  `id` char(36) NOT NULL,
  `error_message` text NOT NULL,
  `request_url` text NOT NULL,
  `referrer_url` text NOT NULL,
  `client_ip` varchar(50) NOT NULL,
  `client_browser` text NOT NULL,
  `triggered_from` text NOT NULL,
  `stack_trace` longtext NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of errors encountered by users';

ALTER TABLE `system_errors` ADD PRIMARY KEY (`id`);


-- POCOR-3563
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3563', NOW());

-- temp_institution_subject_staff
DROP TABLE IF EXISTS `temp_institution_subject_staff`;
CREATE TABLE `temp_institution_subject_staff` LIKE `institution_subject_staff`;

ALTER TABLE `temp_institution_subject_staff` ADD `start_date` DATE NULL AFTER `id`, ADD `end_date` DATE NULL AFTER `start_date`;
ALTER TABLE `temp_institution_subject_staff` ADD `institution_id` INT(11) NOT NULL COMMENT 'links to institutions.id' AFTER `staff_id`, ADD INDEX (`institution_id`);

INSERT INTO `temp_institution_subject_staff`
SELECT `ISS`.`id`, `ISS`.`created`, `IST`.`end_date`, `IST`.`staff_id`, `IST`.`institution_id`, `IS`.`id`,
`ISS`.`modified_user_id`, `ISS`.`modified`, `ISS`.`created_user_id`, `ISS`.`created`
FROM `institution_subject_staff` `ISS`
INNER JOIN `institution_subjects` `IS`
    ON `ISS`.`institution_subject_id` = `IS`.`id`
INNER JOIN `institution_staff` `IST`
    ON (
        `ISS`.`staff_id` = `IST`.`staff_id`
        AND `IS`.`institution_id` = `IST`.`institution_id`
    )
GROUP BY `IST`.`staff_id`, `IST`.`institution_id`, `IS`.`id`;

RENAME TABLE `institution_subject_staff` TO `z_3563_institution_subject_staff`;

RENAME TABLE `temp_institution_subject_staff` TO `institution_subject_staff`;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('1ebef019-d3df-11e6-907e-525400b263eb', 'InstitutionSubjects', 'past_teachers', 'Institutions -> Subjects', 'Past Teachers', NULL, NULL, 1, NULL, NULL, 1, '2017-01-07 00:00:00'),
('74436ffe-d63e-11e6-ad42-525400b263eb', 'InstitutionSubjects', 'end_date', 'Institutions -> Subjects', 'End Date', NULL, NULL, 1, NULL, NULL, 1, '2017-01-07 00:00:00'),
('9c0c7533-d63e-11e6-ad42-525400b263eb', 'InstitutionSubjects', 'teacher_name', 'Institutions -> Subjects', 'Teacher Name', NULL, NULL, 1, NULL, NULL, 1, '2017-01-07 00:00:00'),
('f94ed6be-d63e-11e6-ad42-525400b263eb', 'InstitutionSubjects', 'start_date', 'Institutions -> Subjects', 'Start Date', NULL, NULL, 1, NULL, NULL, 1, '2017-01-07 00:00:00');


-- POCOR-3737
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3737', NOW());

-- security_role_functions
-- clean up of unused _edit
CREATE TABLE `z_3737_security_role_functions` LIKE `security_role_functions`;

INSERT INTO `z_3737_security_role_functions`
SELECT * FROM `security_role_functions` WHERE `security_function_id` = 1012;

UPDATE `security_role_functions` SET `_edit`= 0 WHERE `security_function_id` = 1012;

-- security_functions
UPDATE `security_functions` SET `_edit`= NULL WHERE `id`=1012;


-- POCOR-3748
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


-- 3.8.8
UPDATE config_items SET value = '3.8.8' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
