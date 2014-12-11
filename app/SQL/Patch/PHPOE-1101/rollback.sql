UPDATE 	`security_functions`
SET 	`_edit` = 'InstitutionSiteStudentAbsence.edit', `_delete` = 'InstitutionSiteStudentAbsence.remove'
WHERE 	`security_functions`.`name` = 'Students'
		AND `security_functions`.`controller` = 'InstitutionSites'
		AND `security_functions`.`module` = 'Institutions'
		AND `security_functions`.`category` = 'Attendance';

UPDATE 	`security_functions`
SET 	`_edit` = 'InstitutionSiteStaffAbsence.edit', `_delete` = 'InstitutionSiteStaffAbsence.remove'
WHERE 	`security_functions`.`name` = 'Staff'
		AND `security_functions`.`controller` = 'InstitutionSites'
		AND `security_functions`.`module` = 'Institutions'
		AND `security_functions`.`category` = 'Attendance';

UPDATE 	`security_functions`
SET 	`_add` = 'usersAdd'
WHERE 	`security_functions`.`name` = 'Users'
		AND `security_functions`.`controller` = 'Security'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Accounts &amp; Security';

UPDATE 	`security_functions`
SET 	`_edit` = 'EducationLevel.edit', `_add` = 'EducationLevel.add'
WHERE 	`security_functions`.`name` = 'Education Levels'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET 	`_edit` = 'EducationCycle.edit', `_add` = 'EducationCycle.add'
WHERE 	`security_functions`.`name` = 'Education Cycles'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET `_edit` = 'EducationProgramme.edit', `_add` = 'EducationProgramme.add'
WHERE 	`security_functions`.`name` = 'Education Programmes'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET `_edit` = 'EducationGrade.edit', `_add` = 'EducationGrade.add'
WHERE 	`security_functions`.`name` = 'Education Grades'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET `_edit` = 'EducationGradeSubject.edit'
WHERE 	`security_functions`.`name` = 'Education Grade - Subjects'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET `_edit` = 'EducationSubject.edit', `_add` = 'EducationSubject.add'
WHERE 	`security_functions`.`name` = 'Education Subjects'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET `_edit` = 'EducationCertification.edit', `_add` = 'EducationCertification.add'
WHERE 	`security_functions`.`name` = 'Education Certifications'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET `_edit` = 'EducationFieldOfStudy.edit', `_add` = 'EducationFieldOfStudy.add'
WHERE 	`security_functions`.`name` = 'Education Field of Study'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET `_edit` = 'EducationProgrammeOrientation.edit', `_add` = 'EducationProgrammeOrientation.add'
WHERE 	`security_functions`.`name` = 'Education Programme Orientations'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';