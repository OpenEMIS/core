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

INSERT INTO `db_patches` VALUES ('PHPOE-1882');

UPDATE `security_functions` SET `order` = 1002 WHERE `id` = 1001;
UPDATE `security_functions` SET `order` = 1001 WHERE `id` = 1002;
UPDATE `security_functions` SET `order` = 1006 WHERE `id` = 1004;
UPDATE `security_functions` SET `order` = 1004 WHERE `id` = 1006;

UPDATE `security_functions` SET `category` = 'Staff', `order` = 1016 WHERE `id` = 1003;
UPDATE `security_functions` SET `category` = 'Academic' WHERE `id` IN (1004, 1006, 1007, 1008, 1009, 1010);
UPDATE `security_functions` SET 
`name` = 'Promotion', 
`category` = 'Students',
`_view` = NULL,
`_execute` = 'Promotion.index|Promotion.indexEdit',
`order` = 1024
WHERE `id` = 1005;

UPDATE `security_functions` SET `category` = 'General', `order` = 2002 WHERE `id` = 2010;
UPDATE `security_functions` SET `category` = 'Academic' WHERE `id` IN (2007, 2011, 2012, 2013, 2014, 2015, 2016, 2017);
UPDATE `security_functions` SET `order` = 2017 WHERE `id` = 2007;

UPDATE `security_functions` SET `category` = 'Career' WHERE `id` IN (3012, 3013, 3014, 3015, 3016, 3017, 3019);
UPDATE `security_functions` SET `category` = 'Career', `order` = 3018 WHERE `id` = 3007;
UPDATE `security_functions` SET `category` = 'Professional Development' WHERE `id` IN (3010, 3011, 3018, 3021, 3022);
UPDATE `security_functions` SET `name` = 'Salaries', `category` = 'Finance' WHERE `id` = 3020;

UPDATE `security_functions` SET `name` = 'Trainings' WHERE `id` = 3011;
RENAME TABLE `staff_training` TO `staff_trainings`;
ALTER TABLE `staff_trainings` CHANGE `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_trainings` DROP INDEX security_user_id;
ALTER TABLE `staff_trainings` ADD INDEX(`staff_id`);

INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES
('Guardians', 'openemis_no', 'OpenEMIS ID', 1, NOW()),
('Guardians', 'photo_content', 'Photo', 1, NOW());

UPDATE `student_statuses` SET `name` = 'Enrolled' WHERE `student_statuses`.`id` = 1;

DELETE FROM `security_functions` WHERE `id` >= 6000;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(6000, 'Institution', 'Reports', 'Reports', 'Reports', -1, 'Institutions.index', NULL, 'Institutions.add', NULL, 'Institutions.download', 6000, 1, 1, NOW()),
(6001, 'Students', 'Reports', 'Reports', 'Reports', -1, 'Students.index', NULL, 'Students.add', NULL, 'Students.download', 6001, 1, 1, NOW()),
(6002, 'Staff', 'Reports', 'Reports', 'Reports', -1, 'Staff.index', NULL, 'Staff.add', NULL, 'Staff.download', 6002, 1, 1, NOW());

DELETE FROM `student_statuses` WHERE `code` = 'EXPELLED';

UPDATE `security_functions` SET `_execute` = 'Students.excel' WHERE `id` = 1012;
UPDATE `security_functions` SET `_execute` = 'Staff.excel' WHERE `id` = 1016;

UPDATE `config_items` SET `value` = '3.0.8' WHERE `code` = 'db_version';
