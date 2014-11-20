UPDATE `navigations` SET `action` = 'attendanceStudent', `pattern` = 'attendanceStudent' WHERE `controller` = 'InstitutionSites' AND `header` = 'Attendance' AND `title` = 'Students';

UPDATE `navigations` SET `action` = 'attendanceStaff', `pattern` = 'attendanceStaff' WHERE `controller` = 'InstitutionSites' AND `header` = 'Attendance' AND `title` = 'Staff';

UPDATE `security_functions` SET `_view` = 'staffAttendance', `_edit` = '_view:staffAttendanceEdit', `_add` = NULL, `_delete` = NULL 
WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Details' AND `name` LIKE 'Staff - Attendance';

UPDATE `security_functions` SET `_view` = 'classesAttendance', `_edit` = '_view:classesAttendanceEdit', 
`name` = 'Classes - Attendance', `_add` = NULL, `_delete` = NULL 
WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Details' AND `name` LIKE 'Students - Attendance';