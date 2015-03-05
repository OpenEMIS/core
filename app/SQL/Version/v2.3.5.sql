-- commit.sql
-- PHPOE-1266

UPDATE `security_functions`
SET
  `_view`='InstitutionSiteStaff.index', `_execute`='_view:InstitutionSiteStaff.excel'
WHERE
  `name`='Staff' AND `controller`='Staff' AND `module`='Institutions' AND `category`='Details';

UPDATE `security_functions`
SET
  `_view`='InstitutionSiteStudent.index', `_execute`='_view:InstitutionSiteStudent.excel'
WHERE
  `name`='Student' AND `controller`='Students' AND `module`='Institutions' AND `category`='Details';
