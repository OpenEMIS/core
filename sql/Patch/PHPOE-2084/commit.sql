-- PHPOE-2084
INSERT INTO `db_patches` VALUES ('PHPOE-2084', NOW());

CREATE TABLE `z2084_import_mapping` LIKE `import_mapping`;
INSERT INTO `z2084_import_mapping` SELECT * FROM `import_mapping`;

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
values
('StaffAbsences', 'start_date', '', '2', '0', NULL, NULL, NULL),
('StaffAbsences', 'end_date', '', '3', '0', NULL, NULL, NULL),
('StaffAbsences', 'comment', '', '4', '0', NULL, NULL, NULL),
('StaffAbsences', 'security_user_id', '(OpenEMIS No)', '5', '2', 'Security', 'Users', 'openemis_no'),
('StaffAbsences', 'institution_site_id', '(May Leave blank)', '6', '2', 'Institution', 'Institutions', 'code'),
('StaffAbsences', 'staff_absence_reason_id', '', '7', '1', 'FieldOption', 'StaffAbsenceReasons', 'national_code')
;

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
values
('StudentAbsences', 'start_date', '', '2', '0', NULL, NULL, NULL),
('StudentAbsences', 'end_date', '', '3', '0', NULL, NULL, NULL),
('StudentAbsences', 'comment', '', '4', '0', NULL, NULL, NULL),
('StudentAbsences', 'security_user_id', '(OpenEMIS No)', '5', '2', 'Security', 'Users', 'openemis_no'),
('StudentAbsences', 'institution_site_id', '(May Leave blank)', '6', '2', 'Institution', 'Institutions', 'code'),
('StudentAbsences', 'student_absence_reason_id', '', '7', '1', 'FieldOption', 'StudentAbsenceReasons', 'national_code')
;
