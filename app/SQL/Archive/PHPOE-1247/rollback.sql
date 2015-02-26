--
-- rollback.sql `security_functions`
--

UPDATE `security_functions`
SET `_delete`='_view:InstitutionSiteSection.delete'
WHERE 
	`name`='Sections' 
AND
	`controller`='InstitutionSites'
AND
	`module`='Institutions'
AND
	`category`='Details';


