UPDATE `security_functions` 
SET 
	`name` = 'List of Reports', 
	`controller` = 'InstitutionReports', 
	`_view` = 'index', 
	`_execute` = 'download'
WHERE `name` = 'General' 
AND `controller` = 'Reports' 
AND `module` = 'Reports' 
AND `category` = 'Institutions';

UPDATE `security_functions` 
SET 
	`name` = 'Generate', 
	`controller` = 'InstitutionReports', 
	`_view` = NULL,
	`_execute` = 'generate'
WHERE `name` = 'Details' 
AND `controller` = 'Reports' 
AND `module` = 'Reports' 
AND `category` = 'Institutions';

DELETE FROM `security_functions` WHERE `controller` = 'Reports' AND `module` = 'Reports' AND `category` = 'Institutions';

UPDATE `security_functions` 
SET 
	`name` = 'List of Reports', 
	`controller` = 'StudentReports', 
	`_view` = 'index', 
	`_execute` = 'download'
WHERE `name` = 'General' 
AND `controller` = 'Reports' 
AND `module` = 'Reports' 
AND `category` = 'Students';

UPDATE `security_functions` 
SET 
	`name` = 'Generate', 
	`controller` = 'StudentReports', 
	`_view` = NULL,
	`_execute` = 'generate'
WHERE `name` = 'Details' 
AND `controller` = 'Reports' 
AND `module` = 'Reports' 
AND `category` = 'Students';

DELETE FROM `security_functions` WHERE `controller` = 'Reports' AND `module` = 'Reports' AND `category` = 'Students';

UPDATE `security_functions` 
SET 
	`name` = 'List of Reports', 
	`controller` = 'StaffReports', 
	`_view` = 'index', 
	`_execute` = 'download'
WHERE `name` = 'General' 
AND `controller` = 'Reports' 
AND `module` = 'Reports' 
AND `category` = 'Staff';

UPDATE `security_functions` 
SET 
	`name` = 'Generate', 
	`controller` = 'StaffReports', 
	`_view` = NULL,
	`_execute` = 'generate'
WHERE `name` = 'Details' 
AND `controller` = 'Reports' 
AND `module` = 'Reports' 
AND `category` = 'Staff';

DELETE FROM `security_functions` WHERE `controller` = 'Reports' AND `module` = 'Reports' AND `category` = 'Staff';
