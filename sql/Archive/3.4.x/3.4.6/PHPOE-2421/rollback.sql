-- 
-- PHPOE-2421
--

DROP TABLE `import_mapping`;
ALTER TABLE `z_2421_import_mapping` RENAME `import_mapping`;

UPDATE `security_functions` SET `_execute` = 'ImportInstitutionSurveys.add|ImportInstitutionSurveys.template|ImportInstitutionSurveys.results|ImportInstitutionSurveys.downloadFailed' WHERE `security_functions`.`id` = 1024;
UPDATE `security_functions` SET `_execute` = 'ImportInstitutions.add|ImportInstitutions.template|ImportInstitutions.results|ImportInstitutions.downloadFailed' WHERE `security_functions`.`id` = 1034;
UPDATE `security_functions` SET `_execute` = 'ImportStudents.add|ImportStudents.template|ImportStudents.results|ImportStudents.downloadFailed' WHERE `security_functions`.`id` = 1035;
UPDATE `security_functions` SET `_execute` = 'ImportStudentAttendances.add|ImportStudentAttendances.template|ImportStudentAttendances.results|ImportStudentAttendances.downloadFailed' WHERE `security_functions`.`id` = 1036;
UPDATE `security_functions` SET `_execute` = 'ImportStaffAttendances.add|ImportStaffAttendances.template|ImportStaffAttendances.results|ImportStaffAttendances.downloadFailed' WHERE `security_functions`.`id` = 1037;
UPDATE `security_functions` SET `_execute` = 'ImportUsers.add|ImportUsers.template|ImportUsers.results|ImportUsers.downloadFailed' WHERE `security_functions`.`id` = 7036;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2421';
