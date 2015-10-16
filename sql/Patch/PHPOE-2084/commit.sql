-- PHPOE-2084
INSERT INTO `db_patches` VALUES ('PHPOE-2084', NOW());

CREATE TABLE `z2084_import_mapping` LIKE `import_mapping`;
INSERT INTO `z2084_import_mapping` SELECT * FROM `import_mapping`;

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
values
('StaffAbsences', 'start_date', '', '1', '0', NULL, NULL, NULL),
('StaffAbsences', 'end_date', '', '2', '0', NULL, NULL, NULL),
('StaffAbsences', 'full_day', '', '3', '0', NULL, NULL, NULL),
('StaffAbsences', 'start_time', '', '4', '0', NULL, NULL, NULL),
('StaffAbsences', 'end_time', '', '5', '0', NULL, NULL, NULL),
('StaffAbsences', 'comment', '', '6', '0', NULL, NULL, NULL),
('StaffAbsences', 'security_user_id', '', '7', '2', 'Security', 'Users', 'openemis_no'),
('StaffAbsences', 'institution_site_id', '(Leave blank)', '8', '2', 'Institution', 'Institutions', 'code'),
('StaffAbsences', 'staff_absence_reason_id', '', '9', '1', 'FieldOption', 'StaffAbsenceReasons', 'national_code')
;

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
values
('StudentAbsences', 'start_date', '', '1', '0', NULL, NULL, NULL),
('StudentAbsences', 'end_date', '', '2', '0', NULL, NULL, NULL),
('StudentAbsences', 'full_day', '', '3', '0', NULL, NULL, NULL),
('StudentAbsences', 'start_time', '', '4', '0', NULL, NULL, NULL),
('StudentAbsences', 'end_time', '', '5', '0', NULL, NULL, NULL),
('StudentAbsences', 'comment', '', '6', '0', NULL, NULL, NULL),
('StudentAbsences', 'security_user_id', '', '7', '2', 'Security', 'Users', 'openemis_no'),
('StudentAbsences', 'institution_site_id', '(Leave blank)', '8', '2', 'Institution', 'Institutions', 'code'),
('StudentAbsences', 'student_absence_reason_id', '', '9', '1', 'FieldOption', 'StudentAbsenceReasons', 'national_code')
;
