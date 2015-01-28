DROP TABLE IF EXISTS `report_progress`;

UPDATE `navigations` SET `plugin` = 'Reports', `controller` = 'Reports', `title` = 'General', `action` = 'InstitutionGeneral', `pattern` = 'InstitutionGeneral' WHERE `parent` = -1 AND `controller` = 'InstitutionReports' AND `title` = 'List of Reports' AND `action` = 'index';
UPDATE `navigations` SET `plugin` = 'Reports', `controller` = 'Reports', `title` = 'Details', `action` = 'InstitutionDetails', `pattern` = 'InstitutionDetails' WHERE `controller` = 'InstitutionReports' AND `title` = 'Generate' AND `action` = 'generate';

