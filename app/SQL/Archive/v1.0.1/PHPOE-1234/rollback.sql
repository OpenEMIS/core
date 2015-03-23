UPDATE `security_functions` SET `_delete` = '_view:InstitutionSiteClass.delete' 
WHERE `module` LIKE 'Institutions' AND 
`category` LIKE 'Details' AND 
`name` LIKE 'Classes';

UPDATE `security_functions` SET `_view` = 'InstitutionSiteStudentAbsence|InstitutionSiteStudentAbsence.index|InstitutionSiteStudentAbsence.view|InstitutionSiteStudentAbsence.absence' 
WHERE `module` LIKE 'Institutions' AND 
`category` LIKE 'Attendance' AND 
`name` LIKE 'Students';

UPDATE `security_functions` SET `_view` = 'InstitutionSiteStaffAbsence|InstitutionSiteStaffAbsence.index|InstitutionSiteStaffAbsence.view|InstitutionSiteStaffAbsence.absence' 
WHERE `module` LIKE 'Institutions' AND 
`category` LIKE 'Attendance' AND 
`name` LIKE 'Staff';


