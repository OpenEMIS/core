-- REFERENCE
-- INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
-- (16, 'Students', 'InstitutionSites', 'Institutions', 'Attendance', 8, 'InstitutionSiteStudentAbsence|InstitutionSiteStudentAbsence.index|InstitutionSiteStudentAbsence.absence', 'InstitutionSiteStudentAbsence.edit', 'InstitutionSiteStudentAbsence.add', 'InstitutionSiteStudentAbsence.remove', NULL, 9, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
-- (29, 'Staff', 'InstitutionSites', 'Institutions', 'Attendance', 8, 'InstitutionSiteStaffAbsence|InstitutionSiteStaffAbsence.index|InstitutionSiteStaffAbsence.absence', 'InstitutionSiteStaffAbsence.edit', 'InstitutionSiteStaffAbsence.add', 'InstitutionSiteStaffAbsence.remove', NULL, 10, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

UPDATE security_functions set _view = replace(_view , '|InstitutionSiteStudentAbsence.dayview','') WHERE category = 'Attendance' AND name = 'students';
UPDATE security_functions set _edit = replace(_edit , '|InstitutionSiteStudentAbsence.dayedit','') WHERE category = 'Attendance' AND name = 'students';

UPDATE security_functions set _view = replace(_view , '|InstitutionSiteStaffAbsence.dayview','') WHERE category = 'Attendance' AND name = 'staff';
UPDATE security_functions set _edit = replace(_edit , '|InstitutionSiteStaffAbsence.dayedit','') WHERE category = 'Attendance' AND name = 'staff';