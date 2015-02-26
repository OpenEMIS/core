UPDATE 	`security_functions`
SET 	`_edit` = '_view:InstitutionSiteStudentAbsence.edit', `_delete` = '_view:InstitutionSiteStudentAbsence.remove'
WHERE 	`security_functions`.`name` = 'Students'
		AND `security_functions`.`controller` = 'InstitutionSites'
		AND `security_functions`.`module` = 'Institutions'
		AND `security_functions`.`category` = 'Attendance';

UPDATE 	`security_functions`
SET 	`_edit` = '_view:InstitutionSiteStaffAbsence.edit', `_delete` = '_view:InstitutionSiteStaffAbsence.remove'
WHERE 	`security_functions`.`name` = 'Staff'
		AND `security_functions`.`controller` = 'InstitutionSites'
		AND `security_functions`.`module` = 'Institutions'
		AND `security_functions`.`category` = 'Attendance';

UPDATE 	`security_functions`
SET 	`_add` = '_view:usersAdd'
WHERE 	`security_functions`.`name` = 'Users'
		AND `security_functions`.`controller` = 'Security'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Accounts &amp; Security';

UPDATE 	`security_functions`
SET 	`_edit` = '_view:EducationLevel.edit', `_add` = '_view:EducationLevel.add'
WHERE 	`security_functions`.`name` = 'Education Levels'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET 	`_edit` = '_view:EducationCycle.edit', `_add` = '_view:EducationCycle.add'
WHERE 	`security_functions`.`name` = 'Education Cycles'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET `_edit` = '_view:EducationProgramme.edit', `_add` = '_view:EducationProgramme.add'
WHERE 	`security_functions`.`name` = 'Education Programmes'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET `_edit` = '_view:EducationGrade.edit', `_add` = '_view:EducationGrade.add'
WHERE 	`security_functions`.`name` = 'Education Grades'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET `_edit` = '_view:EducationGradeSubject.edit'
WHERE 	`security_functions`.`name` = 'Education Grade - Subjects'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET `_edit` = '_view:EducationSubject.edit', `_add` = '_view:EducationSubject.add'
WHERE 	`security_functions`.`name` = 'Education Subjects'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET `_edit` = '_view:EducationCertification.edit', `_add` = '_view:EducationCertification.add'
WHERE 	`security_functions`.`name` = 'Education Certifications'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET `_edit` = '_view:EducationFieldOfStudy.edit', `_add` = '_view:EducationFieldOfStudy.add'
WHERE 	`security_functions`.`name` = 'Education Field of Study'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';

UPDATE 	`security_functions`
SET `_edit` = '_view:EducationProgrammeOrientation.edit', `_add` = '_view:EducationProgrammeOrientation.add'
WHERE 	`security_functions`.`name` = 'Education Programme Orientations'
		AND `security_functions`.`controller` = 'Education'
		AND `security_functions`.`module` = 'Administration'
		AND `security_functions`.`category` = 'Education';