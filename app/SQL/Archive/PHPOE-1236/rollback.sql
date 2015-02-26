UPDATE `security_functions` 
SET 
	`name` = 'General', 
	`controller` = 'Reports', 
	`_view` = 'InstitutionGeneral', 
	`_execute` = '_view:InstitutionGeneralDownload|InstitutionGeneralViewHtml' 
WHERE `name` = 'List of Reports' 
AND `controller` = 'InstitutionReports' 
AND `module` = 'Reports' 
AND `category` = 'Institutions';

UPDATE `security_functions` 
SET 
	`name` = 'Details', 
	`controller` = 'Reports', 
	`_view` = 'InstitutionDetails',
	`_execute` = 'generate'
WHERE `name` = 'Generate' 
AND `controller` = '_view:InstitutionDetailsDownload|InstitutionDetailsViewHtml' 
AND `module` = 'Reports' 
AND `category` = 'Institutions';

UPDATE `security_functions` 
SET 
	`name` = 'General', 
	`controller` = 'Reports', 
	`_view` = 'StudentGeneral', 
	`_execute` = '_view:StudentGeneralDownload|StudentGeneralViewHtml' 
WHERE `name` = 'List of Reports' 
AND `controller` = 'StudentReports' 
AND `module` = 'Reports' 
AND `category` = 'Students';

UPDATE `security_functions` 
SET 
	`name` = 'Details', 
	`controller` = 'Reports', 
	`_view` = 'StudentDetails',
	`_execute` = '_view:StudentDetailsDownload|StudentDetailsViewHtml'
WHERE `name` = 'Generate' 
AND `controller` = 'StudentReports' 
AND `module` = 'Reports' 
AND `category` = 'Students';

UPDATE `security_functions` 
SET 
	`name` = 'General', 
	`controller` = 'Reports', 
	`_view` = 'StaffGeneral', 
	`_execute` = '_view:StaffGeneralDownload|StaffGeneralViewHtml' 
WHERE `name` = 'List of Reports' 
AND `controller` = 'StaffReports' 
AND `module` = 'Reports' 
AND `category` = 'Staff';

UPDATE `security_functions` 
SET 
	`name` = 'Details', 
	`controller` = 'Reports', 
	`_view` = 'StaffDetails',
	`_execute` = '_view:StaffDetailsDownload|StaffDetailsViewHtml'
WHERE `name` = 'Generate' 
AND `controller` = 'StaffReports' 
AND `module` = 'Reports' 
AND `category` = 'Staff';
