UPDATE security_functions SET _view = CONCAT(_view, '|InstitutionSiteStudentAbsence.dayview') WHERE category = 'Attendance' AND name = 'students';
UPDATE security_functions SET _edit = CONCAT(_edit, '|InstitutionSiteStudentAbsence.dayedit') WHERE category = 'Attendance' AND name = 'students';

UPDATE security_functions SET _view = CONCAT(_view, '|InstitutionSiteStaffAbsence.dayview') WHERE category = 'Attendance' AND name = 'staff';
UPDATE security_functions SET _edit = CONCAT(_edit, '|InstitutionSiteStaffAbsence.dayedit') WHERE category = 'Attendance' AND name = 'staff';
