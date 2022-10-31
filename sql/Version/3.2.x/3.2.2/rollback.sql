-- security_functions
UPDATE `security_functions` SET `_view` = 'Students.index|Students.view|StudentUser.view', `_edit` = 'Students.edit|StudentUser.edit' WHERE `id` = 1012;

-- Drop tables
DROP TABLE IF EXISTS `institution_student_surveys`;
DROP TABLE IF EXISTS `institution_student_survey_answers`;
DROP TABLE IF EXISTS `institution_student_survey_table_cells`;

DROP TABLE IF EXISTS `survey_question_params`;

-- Drop Student List
ALTER TABLE `custom_modules` DROP `supported_field_types`;

DELETE FROM `custom_modules` WHERE `model` = 'Student.StudentSurveys';
DELETE FROM `custom_modules` WHERE `model` = 'Staff.StaffSurveys';

-- Drop Student List
DELETE FROM `custom_field_types` WHERE `code` = 'STUDENT_LIST';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1346';

UPDATE security_functions SET _execute = NULL WHERE security_functions.id = 1012;
UPDATE security_functions SET _execute = NULL WHERE security_functions.id = 1016;

-- security_functions
UPDATE `security_functions` SET `_execute` = 'TransferRequests.add|TransferRequests.edit' WHERE `id` = 1022;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2016';

UPDATE config_items SET default_value = 3 WHERE code = 'institution_area_level_id';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2036';

-- security_functions
UPDATE `security_functions` SET `_edit` = 'StudentAttendances.edit|StudentAbsences.edit' WHERE `id` = 1014;
UPDATE `security_functions` SET `_edit` = 'StaffAttendances.edit|StaffAbsences.edit' WHERE `id` = 1018;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2063';


-- security_function
UPDATE `security_functions` SET `_view`=null, `_delete`=null WHERE `id`='1022';

-- labels
DELETE FROM `labels` WHERE `module`='TransferRequests' and `field`='created';

-- student_statuses
INSERT INTO `student_statuses` (`id`, `code`, `name`) VALUES (10, 'REJECTED', 'Rejected');

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2072';

-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Results.index' WHERE `id` = 1015;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2088';

-- institution student dropout
DROP TABLE IF EXISTS `institution_student_dropout`;

-- dummy data for the student dropout reasons
DELETE FROM `field_option_values`
WHERE `field_option_values`.`id` = (SELECT `field_options`.`id` FROM `field_options` WHERE `code` = 'StudentDropoutReasons') AND `field_option_values`.`name`='Relocation';

-- field_options
DELETE FROM `field_options` WHERE `plugin`='Students' AND `code`='StudentDropoutReasons';

-- student_statuses
DELETE FROM `student_statuses` WHERE `code` = 'PENDING_DROPOUT';

-- security_functions
DELETE FROM `security_functions` WHERE `id`=1030;
DELETE FROM `security_functions` WHERE `id`=1031;

-- label
DELETE FROM `labels` WHERE `module`='StudentDropout' and `field`='created';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2019';

UPDATE config_items SET name = 'Student Admission Age' WHERE config_items.code = 'admission_age_plus';
UPDATE config_items SET label = 'Student Admission Age' WHERE config_items.code = 'admission_age_plus';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1919';

-- institution_site_programmes
ALTER TABLE `z_2117_institution_site_programmes` 
RENAME TO  `institution_site_programmes` ;

-- institution_site_grades
ALTER TABLE `institution_site_grades` 
ADD COLUMN `institution_site_programme_id` INT(11) NOT NULL DEFAULT 0 AFTER `end_year`,
ADD INDEX `institution_site_programme_id` (`institution_site_programme_id` ASC);


ALTER TABLE `institution_site_grades` 
CHANGE COLUMN `institution_site_programme_id` `institution_site_programme_id` INT(11) NOT NULL;

UPDATE `institution_site_grades`
LEFT JOIN `z_2117_institution_site_grades` ON `institution_site_grades`.`id` = `z_2117_institution_site_grades`.`id`
  SET `institution_site_grades`.`institution_site_programme_id` = `z_2117_institution_site_grades`.`institution_site_programme_id`
  WHERE `institution_site_grades`.`id` = `z_2117_institution_site_grades`.`id`;
DROP TABLE `z_2117_institution_site_grades`;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES 
(uuid(), 'InstitutionSiteProgrammes', 'created_user_id', 'Institutions -> Programmes', 'Created By', '1', '0', NOW()),
(uuid(), 'InstitutionSiteProgrammes', 'created', 'Institutions -> Programmes', 'Created On', '1', '0', NOW()),
(uuid(), 'InstitutionSiteProgrammes', 'created_user_id', 'Institutions -> Programmes', 'Created By', '1', '0', NOW()),
(uuid(), 'InstitutionSiteProgrammes', 'education_programme_id', 'Institutions -> Programmes', 'Programme', '1', '0', NOW()),
(uuid(), 'InstitutionSiteProgrammes', 'modified', 'Institutions -> Programmes', 'Modified On', '1', '0', NOW()),
(uuid(), 'InstitutionSiteProgrammes', 'modified_user_id', 'Institutions -> Programmes', 'Modified By', '1', '0', NOW()),
(uuid(), 'InstitutionSiteProgrammes', 'openemis_no', 'Institutions -> Programmes', 'OpenEMIS ID', '1', '0', NOW());

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2117';

UPDATE `config_items` SET `value` = '3.2.1' WHERE `code` = 'db_version';

