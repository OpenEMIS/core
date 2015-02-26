UPDATE `navigations` SET `action` = 'InstitutionSiteStudentAbsence', `pattern` = 'InstitutionSiteStudentAbsence' WHERE `controller` = 'InstitutionSites' AND `header` = 'Attendance' AND `title` = 'Students';

UPDATE `navigations` SET `action` = 'InstitutionSiteStaffAbsence', `pattern` = 'InstitutionSiteStaffAbsence' WHERE `controller` = 'InstitutionSites' AND `header` = 'Attendance' AND `title` = 'Staff';

UPDATE `security_functions` SET `_view` = 'InstitutionSiteStaffAbsence|InstitutionSiteStaffAbsence.index|InstitutionSiteStaffAbsence.absence', 
`_edit` = 'InstitutionSiteStaffAbsence.edit', `_add` = 'InstitutionSiteStaffAbsence.add', 
`_delete` = 'InstitutionSiteStaffAbsence.remove' WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Details' AND `name` LIKE 'Staff - Attendance';

UPDATE `security_functions` SET `_view` = 'InstitutionSiteStudentAbsence|InstitutionSiteStudentAbsence.index|InstitutionSiteStudentAbsence.absence', 
`_edit` = 'InstitutionSiteStudentAbsence.edit', `_add` = 'InstitutionSiteStudentAbsence.add', 
`_delete` = 'InstitutionSiteStudentAbsence.remove', `name` = 'Students - Attendance' 
WHERE `module` LIKE 'Institutions' AND `category` LIKE 'Details' AND `name` LIKE 'Classes - Attendance';
