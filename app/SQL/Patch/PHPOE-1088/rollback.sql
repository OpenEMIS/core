UPDATE 	`navigations`
SET 	`order` = '5'
WHERE 	`navigations`.`module` = 'Institution'
		AND `navigations`.`controller` = 'InstitutionSites'
		AND `navigations`.`action` = 'attachments';

UPDATE 	`navigations`
SET 	`order` = '7'
WHERE 	`navigations`.`module` = 'Institution'
		AND `navigations`.`controller` = 'InstitutionSites'
		AND `navigations`.`action` = 'additional';

UPDATE 	`navigations`
SET 	`order` = '9'
WHERE 	`navigations`.`module` = 'Institution'
		AND `navigations`.`controller` = 'InstitutionSites'
		AND `navigations`.`action` = 'InstitutionSitePosition';

UPDATE 	`navigations`
SET 	`header` = 'General', `order` = '4'
WHERE 	`navigations`.`module` = 'Institution'
		AND `navigations`.`controller` = 'InstitutionSites'
		AND `navigations`.`action` = 'shifts';

UPDATE 	`security_functions`
SET 	`category` = 'General', `order` = '3'
WHERE 	`security_functions`.`name` = 'Shifts'
		AND `security_functions`.`controller` = 'InstitutionSites'
		AND `security_functions`.`module` = 'Institutions';

UPDATE 	`security_functions`
SET 	`order` = '4'
WHERE 	`security_functions`.`name` = 'Attachments'
		AND `security_functions`.`controller` = 'InstitutionSites'
		AND `security_functions`.`module` = 'Institutions';

UPDATE 	`security_functions`
SET 	`order` = '5'
WHERE 	`security_functions`.`name` = 'More'
		AND `security_functions`.`controller` = 'InstitutionSites'
		AND `security_functions`.`module` = 'Institutions';

UPDATE 	`security_functions`
SET 	`order` = '7'
WHERE 	`security_functions`.`name` = 'Positions'
		AND `security_functions`.`controller` = 'InstitutionSites'
		AND `security_functions`.`module` = 'Institutions';