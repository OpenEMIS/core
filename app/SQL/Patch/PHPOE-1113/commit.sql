UPDATE security_functions SET _view = CONCAT(_view, '|InstitutionSiteStudentAbsence.dayview') WHERE category = 'Attendance' AND name = 'students';
UPDATE security_functions SET _add = CONCAT(_add, '|InstitutionSiteStudentAbsence.dayedit') WHERE category = 'Attendance' AND name = 'students';

UPDATE security_functions SET _view = CONCAT(_view, '|InstitutionSiteStaffAbsence.dayview') WHERE category = 'Attendance' AND name = 'staff';
UPDATE security_functions SET _add = CONCAT(_add, '|InstitutionSiteStaffAbsence.dayedit') WHERE category = 'Attendance' AND name = 'staff';
