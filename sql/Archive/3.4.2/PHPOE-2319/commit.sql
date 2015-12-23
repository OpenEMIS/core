-- PHPOE-2319
INSERT INTO `db_patches` VALUES ('PHPOE-2319', NOW());

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES 
(1034, 'Import Institutions', 'Institutions', 'Institutions', 'General', '8', NULL, NULL, NULL, NULL, 'ImportInstitutions.add|ImportInstitutions.results|ImportInstitutions.template', '1034', '1', '1', NOW()),
(1035, 'Import Students', 'Institutions', 'Institutions', 'Students', '1012', NULL, NULL, NULL, NULL, 'ImportStudents.add|ImportStudents.results|ImportStudents.template', '1035', '1', '1', NOW()),
(1036, 'Import Student Attendances', 'Institutions', 'Institutions', 'Students', '1012', NULL, NULL, NULL, NULL, 'ImportStudentAttendances.add|ImportStudentAttendances.results|ImportStudentAttendances.template', '1036', '1', '1', NOW()),
(1037, 'Import Staff Attendances', 'Institutions', 'Institutions', 'Staff', '1016', NULL, NULL, NULL, NULL, 'ImportStaffAttendances.add|ImportStaffAttendances.results|ImportStaffAttendances.template', '1037', '1', '1', NOW()),
(7036, 'Import Users', 'Directories', 'Directory', 'General', '7000', NULL, NULL, NULL, NULL, 'ImportUsers.add|ImportUsers.results|ImportUsers.template', '7036', '1', '1', NOW())
;
