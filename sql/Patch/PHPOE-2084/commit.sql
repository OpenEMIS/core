-- PHPOE-2084
INSERT INTO `db_patches` VALUES ('PHPOE-2084');

CREATE TABLE `z2084_import_mapping` LIKE `import_mapping`;
INSERT INTO `z2084_import_mapping` SELECT * FROM `import_mapping`;

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_alias`, `lookup_column`) 
values
('StaffAttendances', 'start_date', '', '1', '0', NULL, NULL, NULL, NULL),
('StaffAttendances', 'end_date', '', '2', '0', NULL, NULL, NULL, NULL),
('StaffAttendances', 'full_day', '', '3', '0', NULL, NULL, NULL, NULL),
('StaffAttendances', 'start_time', '', '4', '0', NULL, NULL, NULL, NULL),
('StaffAttendances', 'end_time', '', '5', '0', NULL, NULL, NULL, NULL),
('StaffAttendances', 'comment', '', '6', '0', NULL, NULL, NULL, NULL),
('StaffAttendances', 'security_user_id', '', '7', '2', 'Security', 'Users', 'Users', 'id'),
('StaffAttendances', 'institution_site_id', '(Leave blank)', '8', '2', 'Institution', 'Institutions', 'Institutions', 'id'),
('StaffAttendances', 'student_absence_reason_id', '', '10', '1', NULL, NULL, NULL, NULL)
;
