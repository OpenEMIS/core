-- PHPOE-2319
UPDATE `security_functions` set `_execute` = 'ImportInstitutionSurveys.add|ImportInstitutionSurveys.template|ImportInstitutionSurveys.results' where `id`=1024;

DELETE FROM `security_functions` 
WHERE 
`id` = 1034 or
`id` = 1035 or
`id` = 1036 or
`id` = 1037 or
`id` = 7036
;

DELETE FROM `db_patches` WHERE `issue`='PHPOE-2319';
