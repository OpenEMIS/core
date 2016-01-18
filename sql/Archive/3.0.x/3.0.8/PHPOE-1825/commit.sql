INSERT INTO `db_patches` VALUES ('PHPOE-1825');

ALTER TABLE `student_guardians` CHANGE `id` `id` CHAR(36) NOT NULL;
ALTER TABLE `student_guardians` CHANGE `student_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_guardians` CHANGE `guardian_user_id` `guardian_id` INT(11) NOT NULL COMMENT 'links to security_users.id';

ALTER TABLE `security_users` ADD `is_student` INT(1) NOT NULL DEFAULT '0' AFTER `photo_content`;
ALTER TABLE `security_users` ADD INDEX(`is_student`);
ALTER TABLE `security_users` ADD `is_staff` INT(1) NOT NULL DEFAULT '0' AFTER `is_student`;
ALTER TABLE `security_users` ADD INDEX(`is_staff`);
ALTER TABLE `security_users` ADD `is_guardian` INT(1) NOT NULL DEFAULT '0' AFTER `is_staff`;
ALTER TABLE `security_users` ADD INDEX(`is_guardian`);

UPDATE `security_users`
JOIN `security_user_types` 
ON `security_user_types`.`security_user_id` = `security_users`.`id`
AND `security_user_types`.`user_type` = 1
SET `is_student` = 1;

UPDATE `security_users`
JOIN `security_user_types` 
ON `security_user_types`.`security_user_id` = `security_users`.`id`
AND `security_user_types`.`user_type` = 2
SET `is_staff` = 1;

UPDATE `security_users`
JOIN `security_user_types` 
ON `security_user_types`.`security_user_id` = `security_users`.`id`
AND `security_user_types`.`user_type` = 3
SET `is_guardian` = 1;

RENAME TABLE `security_user_types` TO `z_1825_security_user_types`;

DELETE FROM `security_functions` WHERE `id` >= 4000 AND `id` < 5000;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(4000, 'Guardians', 'Guardians', 'Guardians', 'General', -1, 'index|view', 'edit', 'add', 'remove', 'excel', 4000, 1, 1, NOW()),
(4001, 'Identities', 'Guardians', 'Guardians', 'General', 4000, 'Identities.index|Identities.view', 'Identities.edit', 'Identities.add', 'Identities.remove', NULL, 4001, 1, 1, NOW()),
(4002, 'Nationalities', 'Guardians', 'Guardians', 'General', 4000, 'Nationalities.index|Nationalities.view', 'Nationalities.edit', 'Nationalities.add', 'Nationalities.remove', NULL, 4002, 1, 1, NOW()),
(4003, 'Contacts', 'Guardians', 'Guardians', 'General', 4000, 'Contacts.index|Contacts.view', 'Contacts.edit', 'Contacts.add', 'Contacts.remove', NULL, 4003, 1, 1, NOW()),
(4004, 'Languages', 'Guardians', 'Guardians', 'General', 4000, 'Languages.index|Languages.view', 'Languages.edit', 'Languages.add', 'Languages.remove', NULL, 4004, 1, 1, NOW()),
(4005, 'Attachments', 'Guardians', 'Guardians', 'General', 4000, 'Attachments.index|Attachments.view', 'Attachments.edit', 'Attachments.add', 'Attachments.remove', NULL, 4005, 1, 1, NOW()),
(4006, 'Comments', 'Guardians', 'Guardians', 'General', 4000, 'Comments.index|Comments.view', 'Comments.edit', 'Comments.add', 'Comments.remove', NULL, 4006, 1, 1, NOW()),
(4007, 'History', 'Guardians', 'Guardians', 'General', 4000, 'History.index', NULL, NULL, NULL, NULL, 4007, 1, 1, NOW());

DROP TABLE IF EXISTS `census_staff`;
DROP TABLE IF EXISTS `census_attendances`;
DROP TABLE IF EXISTS `census_assessments`;
DROP TABLE IF EXISTS `datawarehouse_dimensions`;
DROP TABLE IF EXISTS `datawarehouse_fields`;
DROP TABLE IF EXISTS `datawarehouse_indicators`;
DROP TABLE IF EXISTS `datawarehouse_indicator_dimensions`;
DROP TABLE IF EXISTS `datawarehouse_indicator_subgroups`;
DROP TABLE IF EXISTS `datawarehouse_modules`;
DROP TABLE IF EXISTS `datawarehouse_units`;
DROP TABLE IF EXISTS `navigations`;
DROP TABLE IF EXISTS `batch_indicators`;
DROP TABLE IF EXISTS `batch_indicator_subgroups`;
DROP TABLE IF EXISTS `batch_indicator_results`;
DROP TABLE IF EXISTS `olap_cubes`;
DROP TABLE IF EXISTS `olap_cube_dimensions`;
DROP TABLE IF EXISTS `population`;
DROP TABLE IF EXISTS `public_expenditure`;
DROP TABLE IF EXISTS `public_expenditure_education_level`;
DROP TABLE IF EXISTS `1290_institution_site_class_students`;
DROP TABLE IF EXISTS `institution_grade_students`;

-- clean up orphan records
DELETE FROM assessment_item_results				WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = assessment_item_results.security_user_id);
DELETE FROM guardian_activities 				WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = guardian_activities.security_user_id);
DELETE FROM institution_site_class_staff 		WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = institution_site_class_staff.security_user_id);
DELETE FROM institution_site_class_students		WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = institution_site_class_students.student_id);
DELETE FROM institution_site_quality_rubrics	WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = institution_site_quality_rubrics.security_user_id);
DELETE FROM institution_site_quality_visits		WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = institution_site_quality_visits.security_user_id);
DELETE FROM institution_site_section_students 	WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = institution_site_section_students.student_id);
DELETE FROM institution_site_staff 				WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = institution_site_staff.security_user_id);
DELETE FROM institution_site_staff_absences 	WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = institution_site_staff_absences.security_user_id);
DELETE FROM institution_site_student_absences 	WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = institution_site_student_absences.security_user_id);
DELETE FROM institution_site_students 			WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = institution_site_students.security_user_id);
DELETE FROM institution_student_transfers 		WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = institution_student_transfers.security_user_id);
DELETE FROM institution_students 				WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = institution_students.student_id);
DELETE FROM report_templates 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = report_templates.security_user_id);
DELETE FROM security_group_users 				WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = security_group_users.security_user_id);
DELETE FROM security_user_access 				WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = security_user_access.security_user_id);
DELETE FROM staff 								WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff.security_user_id);
DELETE FROM staff_activities 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_activities.security_user_id);
DELETE FROM staff_attendances 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_attendances.security_user_id);
DELETE FROM staff_behaviours 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_behaviours.security_user_id);
DELETE FROM staff_custom_field_values 			WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_custom_field_values.security_user_id);
DELETE FROM staff_custom_table_cells 			WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_custom_table_cells.security_user_id);
DELETE FROM staff_employments 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_employments.security_user_id);
DELETE FROM staff_extracurriculars 				WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_extracurriculars.security_user_id);
DELETE FROM staff_health_allergies 				WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_health_allergies.security_user_id);
DELETE FROM staff_health_consultations 			WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_health_consultations.security_user_id);
DELETE FROM staff_health_families 				WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_health_families.security_user_id);
DELETE FROM staff_health_histories 				WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_health_histories.security_user_id);
DELETE FROM staff_health_immunizations 			WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_health_immunizations.security_user_id);
DELETE FROM staff_health_medications 			WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_health_medications.security_user_id);
DELETE FROM staff_health_tests 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_health_tests.security_user_id);
DELETE FROM staff_healths 						WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_healths.security_user_id);
DELETE FROM staff_leaves 						WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_leaves.security_user_id);
DELETE FROM staff_licenses 						WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_licenses.security_user_id);
DELETE FROM staff_memberships 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_memberships.security_user_id);
DELETE FROM staff_qualifications 				WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_qualifications.security_user_id);
DELETE FROM staff_salaries 						WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_salaries.security_user_id);
DELETE FROM staff_training 						WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_training.security_user_id);
DELETE FROM staff_training_needs 				WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_training_needs.security_user_id);
DELETE FROM staff_training_self_studies 		WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = staff_training_self_studies.security_user_id);
DELETE FROM student_activities 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_activities.security_user_id);
DELETE FROM student_attendances 				WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_attendances.security_user_id);
DELETE FROM student_behaviours 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_behaviours.security_user_id);
DELETE FROM student_custom_field_values 		WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_custom_field_values.security_user_id);
DELETE FROM student_custom_table_cells 			WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_custom_table_cells.security_user_id);
DELETE FROM student_extracurriculars 			WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_extracurriculars.security_user_id);
DELETE FROM student_fees 						WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_fees.security_user_id);
DELETE FROM student_guardians 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_guardians.student_id);
DELETE FROM student_guardians 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_guardians.guardian_id);
DELETE FROM student_health_allergies 			WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_health_allergies.security_user_id);
DELETE FROM student_health_consultations 		WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_health_consultations.security_user_id);
DELETE FROM student_health_families 			WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_health_families.security_user_id);
DELETE FROM student_health_histories 			WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_health_histories.security_user_id);
DELETE FROM student_health_immunizations		WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_health_immunizations.security_user_id);
DELETE FROM student_health_medications 			WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_health_medications.security_user_id);
DELETE FROM student_health_tests 				WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_health_tests.security_user_id);
DELETE FROM student_healths 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = student_healths.security_user_id);
DELETE FROM students 							WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = students.security_user_id);
DELETE FROM training_session_trainees 			WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = training_session_trainees.security_user_id);
DELETE FROM user_attachments 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = user_attachments.security_user_id);
DELETE FROM user_awards 						WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = user_awards.security_user_id);
DELETE FROM user_bank_accounts 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = user_bank_accounts.security_user_id);
DELETE FROM user_comments 						WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = user_comments.security_user_id);
DELETE FROM user_contacts 						WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = user_contacts.security_user_id);
DELETE FROM user_identities 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = user_identities.security_user_id);
DELETE FROM user_languages 						WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = user_languages.security_user_id);
DELETE FROM user_nationalities 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = user_nationalities.security_user_id);
DELETE FROM user_special_needs 					WHERE NOT EXISTS (SELECT 1 FROM security_users WHERE security_users.id = user_special_needs.security_user_id);

