-- 
-- PHPOE-2421
--

INSERT INTO `db_patches` VALUES ('PHPOE-2421', NOW());

-- DROP TABLE IF EXISTS `z_2403_import_mapping`;

CREATE TABLE `z_2421_import_mapping` LIKE `import_mapping`;
INSERT INTO `z_2421_import_mapping` SELECT * FROM `import_mapping`;

ALTER TABLE `import_mapping` CHANGE `column_name` `column_name` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
UPDATE `import_mapping` SET `id` = `id`+1000 WHERE `id` > 25;
UPDATE `import_mapping` SET `id` = `id`-999 WHERE `id` > 1000;

INSERT INTO `import_mapping` 
(`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
(26, 'Institution.Institutions', 'institution_network_connectivity_id', 'Code', 26, 2, 'Institution', 'NetworkConnectivities', 'id');

ALTER TABLE `import_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=82;

UPDATE `security_functions` SET `_execute` = 'ImportInstitutionSurveys.add|ImportInstitutionSurveys.template|ImportInstitutionSurveys.results|ImportInstitutionSurveys.downloadFailed|ImportInstitutionSurveys.downloadPassed' WHERE `security_functions`.`id` = 1024;
UPDATE `security_functions` SET `_execute` = 'ImportInstitutions.add|ImportInstitutions.template|ImportInstitutions.results|ImportInstitutions.downloadFailed|ImportInstitutions.downloadPassed' WHERE `security_functions`.`id` = 1034;
UPDATE `security_functions` SET `_execute` = 'ImportStudents.add|ImportStudents.template|ImportStudents.results|ImportStudents.downloadFailed|ImportStudents.downloadPassed' WHERE `security_functions`.`id` = 1035;
UPDATE `security_functions` SET `_execute` = 'ImportStudentAttendances.add|ImportStudentAttendances.template|ImportStudentAttendances.results|ImportStudentAttendances.downloadFailed|ImportStudentAttendances.downloadPassed' WHERE `security_functions`.`id` = 1036;
UPDATE `security_functions` SET `_execute` = 'ImportStaffAttendances.add|ImportStaffAttendances.template|ImportStaffAttendances.results|ImportStaffAttendances.downloadFailed|ImportStaffAttendances.downloadPassed' WHERE `security_functions`.`id` = 1037;
UPDATE `security_functions` SET `_execute` = 'ImportUsers.add|ImportUsers.template|ImportUsers.results|ImportUsers.downloadFailed|ImportUsers.downloadPassed' WHERE `security_functions`.`id` = 7036;


UPDATE config_items SET value = '3.4.6' WHERE code = 'db_version';
