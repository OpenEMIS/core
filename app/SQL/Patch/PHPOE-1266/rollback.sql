-- rollback.sql
-- PHPOE-1266

UPDATE `security_functions`
SET
  `_view`='', `_execute`='InstitutionSiteStaff.excel'
WHERE
  `name`='Staff' AND `controller`='Staff' AND `module`='Institutions' AND `category`='Details';

UPDATE `security_functions`
SET
  `_view`='', `_execute`='InstitutionSiteStudent.excel'
WHERE
  `name`='Student' AND `controller`='Students' AND `module`='Institutions' AND `category`='Details';
