UPDATE `security_functions` SET `_execute` = '_view:InstitutionSiteStudentAttendance.excel' WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Attendance' AND `name` LIKE 'Students';

UPDATE `security_functions` SET `_execute` = '_view:InstitutionSiteStaffAttendance.excel' WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Attendance' AND `name` LIKE 'Staff';

UPDATE `security_functions` SET `_execute` = '_view:assessmentsToExcel' WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Assessment' AND `name` LIKE 'Results';