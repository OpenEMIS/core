UPDATE 	`security_functions`
SET 	`_view` = 'InstitutionSiteStudentAbsence|InstitutionSiteStudentAbsence.index|InstitutionSiteStudentAbsence.view|InstitutionSiteStudentAbsence.absence'
WHERE 	`security_functions`.`name` = 'Students'
		AND `security_functions`.`controller` = 'InstitutionSites'
		AND `security_functions`.`module` = 'Institutions'
		AND `security_functions`.`category` = 'Attendance';

UPDATE 	`security_functions`
SET 	`_view` = 'InstitutionSiteStaffAbsence|InstitutionSiteStaffAbsence.index|InstitutionSiteStaffAbsence.view|InstitutionSiteStaffAbsence.absence'
WHERE 	`security_functions`.`name` = 'Staff'
		AND `security_functions`.`controller` = 'InstitutionSites'
		AND `security_functions`.`module` = 'Institutions'
		AND `security_functions`.`category` = 'Attendance';