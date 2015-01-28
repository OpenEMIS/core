DROP TABLE IF EXISTS `report_progress`;

UPDATE `navigations` SET `plugin` = 'Reports', `controller` = 'Reports', `title` = 'General', `action` = 'InstitutionGeneral', `pattern` = 'InstitutionGeneral' WHERE `parent` = -1 AND `controller` = 'InstitutionReports' AND `title` = 'List of Reports' AND `action` = 'index';
UPDATE `navigations` SET `plugin` = 'Reports', `controller` = 'Reports', `title` = 'Details', `action` = 'InstitutionDetails', `pattern` = 'InstitutionDetails' WHERE `controller` = 'InstitutionReports' AND `title` = 'Generate' AND `action` = 'generate';

UPDATE `navigations` SET `plugin` = 'Reports', `controller` = 'Reports', `title` = 'General', `action` = 'StudentGeneral', `pattern` = 'StudentGeneral' WHERE `controller` = 'StudentReports' AND `title` = 'List of Reports' AND `action` = 'index';
UPDATE `navigations` SET `plugin` = 'Reports', `controller` = 'Reports', `title` = 'Details', `action` = 'StudentDetails', `pattern` = 'StudentDetails' WHERE `controller` = 'StudentReports' AND `title` = 'Generate' AND `action` = 'generate';

UPDATE `navigations` SET `visible` = 1 WHERE `controller` = 'Reports' 
AND `action` IN (
	'InstitutionAttendance', 'InstitutionAssessment', 'InstitutionBehaviors', 'InstitutionFinance', 'InstitutionTotals', 'InstitutionQuality',
	'StudentFinance', 'StudentHealth'
);

