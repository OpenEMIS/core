UPDATE `security_functions`
SET `_delete`='_view:InstitutionSiteSection.remove'
WHERE 
	`name`='Sections' 
AND
	`controller`='InstitutionSites'
AND
	`module`='Institutions'
AND
	`category`='Details';


