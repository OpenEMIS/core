-- PHPOE-2084
INSERT INTO `db_patches` VALUES ('PHPOE-2084', NOW());

CREATE TABLE `z2084_import_mapping` LIKE `import_mapping`;
INSERT INTO `z2084_import_mapping` SELECT * FROM `import_mapping`;

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
values
('StaffAbsences', 'start_date', '', '1', '0', NULL, NULL, NULL),
('StaffAbsences', 'end_date', '', '2', '0', NULL, NULL, NULL),
('StaffAbsences', 'comment', '', '3', '0', NULL, NULL, NULL),
('StaffAbsences', 'security_user_id', 'OpenEMIS ID', '4', '2', 'Security', 'Users', 'openemis_no'),
('StaffAbsences', 'staff_absence_reason_id', 'Code', '5', '1', 'FieldOption', 'StaffAbsenceReasons', 'national_code')
;

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
values
('InstitutionSiteStudentAbsences', 'start_date', '', '1', '0', NULL, NULL, NULL),
('InstitutionSiteStudentAbsences', 'end_date', '', '2', '0', NULL, NULL, NULL),
('InstitutionSiteStudentAbsences', 'comment', '', '3', '0', NULL, NULL, NULL),
('InstitutionSiteStudentAbsences', 'security_user_id', 'OpenEMIS ID', '4', '2', 'Security', 'Users', 'openemis_no'),
('InstitutionSiteStudentAbsences', 'student_absence_reason_id', 'Code', '5', '1', 'FieldOption', 'StudentAbsenceReasons', 'national_code')
;

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `created_user_id`, `created`) VALUES
(uuid(), 'InstitutionSiteStudentAbsences', 'start_date', 'Institutions -> Students -> Absences', 'Start Date', NULL, NULL, 1, 0, NOW()),
(uuid(), 'InstitutionSiteStudentAbsences', 'end_date', 'Institutions -> Students -> Absences', 'End Date', NULL, NULL, 1, 0, NOW()),
(uuid(), 'InstitutionSiteStudentAbsences', 'full_day', 'Institutions -> Students -> Absences', 'Full Day', NULL, NULL, 1, 0, NOW()),
(uuid(), 'InstitutionSiteStudentAbsences', 'comment', 'Institutions -> Students -> Absences', 'Comment', NULL, NULL, 1, 0, NOW()),
(uuid(), 'InstitutionSiteStudentAbsences', 'security_user_id', 'Institutions -> Students -> Absences', 'Student', NULL, NULL, 1, 0, NOW()),
(uuid(), 'InstitutionSiteStudentAbsences', 'institution_site_id', 'Institutions -> Students -> Absences', 'Institution', NULL, NULL, 1, 0, NOW()),
(uuid(), 'InstitutionSiteStudentAbsences', 'student_absence_reason_id', 'Institutions -> Students -> Absences', 'Reason', NULL, NULL, 1, 0, NOW()),
(uuid(), 'InstitutionSiteStudentAbsences', 'academic_period_id', 'Institutions -> Students -> Absences', 'Academic Period', NULL, NULL, 1, 0, NOW()),
(uuid(), 'InstitutionSiteStudentAbsences', 'institution_site_section_id', 'Institutions -> Students -> Absences', 'Class', NULL, NULL, 1, 0, NOW()),
(uuid(), 'InstitutionSiteStudentAbsences', 'institution_site_class_id', 'Institutions -> Students -> Absences', 'Subject', NULL, NULL, 1, 0, NOW()),
(uuid(), 'InstitutionSiteStudentAbsences', 'education_grade_id', 'Institutions -> Students -> Absences', 'Grade', NULL, NULL, 1, 0, NOW()),
(uuid(), 'StaffAbsences', 'start_date', 'Institutions -> Staff -> Absences', 'Start Date', NULL, NULL, 1, 0, NOW()),
(uuid(), 'StaffAbsences', 'end_date', 'Institutions -> Staff -> Absences', 'End Date', NULL, NULL, 1, 0, NOW()),
(uuid(), 'StaffAbsences', 'full_day', 'Institutions -> Staff -> Absences', 'Full Day', NULL, NULL, 1, 0, NOW()),
(uuid(), 'StaffAbsences', 'comment', 'Institutions -> Staff -> Absences', 'Comment', NULL, NULL, 1, 0, NOW()),
(uuid(), 'StaffAbsences', 'security_user_id', 'Institutions -> Staff -> Absences', 'Staff', NULL, NULL, 1, 0, NOW()),
(uuid(), 'StaffAbsences', 'institution_site_id', 'Institutions -> Staff -> Absences', 'Institution', NULL, NULL, 1, 0, NOW()),
(uuid(), 'StaffAbsences', 'student_absence_reason_id', 'Institutions -> Staff -> Absences', 'Reason', NULL, NULL, 1, 0, NOW()),
(uuid(), 'StaffAbsences', 'academic_period_id', 'Institutions -> Staff -> Absences', 'Academic Period', NULL, NULL, 1, 0, NOW()),
(uuid(), 'StaffAbsences', 'institution_site_section_id', 'Institutions -> Staff -> Absences', 'Class', NULL, NULL, 1, 0, NOW()),
(uuid(), 'StaffAbsences', 'institution_site_class_id', 'Institutions -> Staff -> Absences', 'Subject', NULL, NULL, 1, 0, NOW()),
(uuid(), 'StaffAbsences', 'education_grade_id', 'Institutions -> Staff -> Absences', 'Grade', NULL, NULL, 1, 0, NOW()),
(uuid(), 'Imports', 'openemis_no', 'Imports', 'OpenEMIS ID', NULL, NULL, 1, 0, NOW()),
(uuid(), 'Imports', 'institution_site_id', 'Imports', 'Institution', NULL, NULL, 1, 0, NOW())
;

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2099', NOW());

-- config_items
INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
('Url', 'support_url', 'Help', 'Url', 'https://support.openemis.org/core/', 'https://support.openemis.org/core/', 0, 1, '', '', 1, NOW());

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2281', NOW());

-- security_functions
UPDATE `security_functions` SET `_execute`='Surveys.excel' WHERE `id`=1025;
UPDATE `security_functions` SET `_execute`='Rubrics.excel' WHERE `id`=1029;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_add`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (6003, 'Surveys', 'Reports', 'Reports', 'Reports', -1, 'Surveys.index', 'Surveys.add', 'Surveys.download', 6003, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_add`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (6004, 'InstitutionRubrics', 'Reports', 'Reports', 'Reports', -1, 'InstitutionRubrics.index', 'InstitutionRubrics.add', 'InstitutionRubrics.download', 6004, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_add`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (6006, 'Audit', 'Reports', 'Reports', 'Reports', -1, 'Audit.index', 'Audit.add', 'Audit.download', 6006, 1, 1, NOW());

UPDATE `config_items` SET `value` = '3.3.4' WHERE `code` = 'db_version';
