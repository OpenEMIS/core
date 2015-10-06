-- PHPOE-2081
INSERT INTO `db_patches` VALUES ('PHPOE-2084');

CREATE TABLE `z2084_import_mapping` LIKE `import_mapping`;
INSERT INTO `z2084_import_mapping` SELECT * FROM `import_mapping`;

  `value` int(11) NOT NULL,
  `student_attendance_type_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `institution_site_class_id` int(11) NOT NULL,

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_alias`, `lookup_column`) 
values
('StudentAttendances', 'value', '', '1', '0', NULL, NULL, NULL, NULL),
('StudentAttendances', 'student_attendance_type_id', '', '2', '1', NULL, NULL, NULL, NULL),
('StudentAttendances', 'academic_period_id', '', '3', '2', 'AcademicPeriod', 'AcademicPeriods', 'AcademicPeriods', 'id'),
('StudentAttendances', 'security_user_id', '', '4', '2', 'Security', 'Users', 'Users', 'id'),
('StudentAttendances', 'institution_site_id', '(Leave blank)', '5', '2', 'Institution', 'Institutions', 'Institutions', 'id'),
('StudentAttendances', 'institution_site_class_id', '', '6', '2', 'Institution', 'InstitutionSiteClasses', 'InstitutionSiteClasses', 'id')
;


