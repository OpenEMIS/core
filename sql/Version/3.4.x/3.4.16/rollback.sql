-- POCOR-1798
DROP TABLE IF EXISTS `employment_types`;
DROP TABLE IF EXISTS `extracurricular_types`;
DROP TABLE IF EXISTS `identity_types`;
DROP TABLE IF EXISTS `languages`;
DROP TABLE IF EXISTS `license_types`;
DROP TABLE IF EXISTS `special_need_types`;

SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'EmploymentTypes';
UPDATE field_options SET params = NULL WHERE id = @fieldOptionId;
SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'ExtracurricularTypes';
UPDATE field_options SET params = NULL WHERE id = @fieldOptionId;
SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'IdentityTypes';
UPDATE field_options SET params = NULL WHERE id = @fieldOptionId;
SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'Languages';
UPDATE field_options SET params = NULL WHERE id = @fieldOptionId;
SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'LicenseTypes';
UPDATE field_options SET params = NULL WHERE id = @fieldOptionId;
SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'SpecialNeedTypes';
UPDATE field_options SET params = NULL WHERE id = @fieldOptionId;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-1798';


-- POCOR-1905
DELETE FROM config_items WHERE code = 'password_min_length';
DELETE FROM config_items WHERE code = 'password_has_uppercase';
DELETE FROM config_items WHERE code = 'password_has_number';
DELETE FROM config_items WHERE code = 'password_has_non_alpha';

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-1905';


-- POCOR-2208
UPDATE labels SET field_name = 'Removable' WHERE module = 'WorkflowSteps' AND module_name = 'Workflow -> Steps' AND field_name = 'Deletable';

-- db_patches
DELETE FROM `db_patches` WHERE  `issue` = 'POCOR-2208';


-- POCOR-2540
-- drop description from workflow_actions
ALTER TABLE `workflow_actions` DROP `description`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2540';


-- POCOR-2562
-- absence_types
DROP TABLE IF EXISTS `absence_types`;

-- institution_staff_absences
ALTER TABLE `institution_staff_absences` 
CHANGE COLUMN `start_time` `start_time` VARCHAR(15) NULL DEFAULT NULL COMMENT '' ,
CHANGE COLUMN `end_time` `end_time` VARCHAR(15) NULL DEFAULT NULL COMMENT '' ;

UPDATE `institution_staff_absences`
INNER JOIN `z_2562_institution_staff_absences` ON `z_2562_institution_staff_absences`.`id` = `institution_staff_absences`.`id`
SET `institution_staff_absences`.`start_time` = `z_2562_institution_staff_absences`.`start_time`, 
`institution_staff_absences`.`end_time` = `z_2562_institution_staff_absences`.`end_time`,
`institution_staff_absences`.`staff_absence_reason_id` = `institution_staff_absences`.`staff_absence_reason_id`;

ALTER TABLE `institution_staff_absences` 
DROP COLUMN `absence_type_id`,
DROP INDEX `absence_type_id` ;

DROP TABLE `z_2562_institution_staff_absences`;

-- institution_student_absences
ALTER TABLE `institution_student_absences` 
CHANGE COLUMN `start_time` `start_time` VARCHAR(15) NULL DEFAULT NULL COMMENT '' ,
CHANGE COLUMN `end_time` `end_time` VARCHAR(15) NULL DEFAULT NULL COMMENT '' ;

UPDATE `institution_student_absences`
INNER JOIN `z_2562_institution_student_absences` ON `z_2562_institution_student_absences`.`id` = `institution_student_absences`.`id`
SET `institution_student_absences`.`start_time` = `z_2562_institution_student_absences`.`start_time`, 
`institution_student_absences`.`end_time` = `z_2562_institution_student_absences`.`end_time`,
`institution_student_absences`.`student_absence_reason_id` = `z_2562_institution_student_absences`.`student_absence_reason_id`;

ALTER TABLE `institution_student_absences` 
DROP COLUMN `absence_type_id`,
DROP INDEX `absence_type_id` ;

DROP TABLE `z_2562_institution_student_absences`;

-- labels
DELETE FROM `labels` WHERE `field` = 'absence_type_id' AND `module` = 'StaffAbsences';
DELETE FROM `labels` WHERE `field` = 'absence_type_id' AND `module` = 'InstitutionStudentAbsences';

-- student_absence_reasons
DROP TABLE `student_absence_reasons`;

-- staff_absence_reasons
DROP TABLE `staff_absence_reasons`;

-- field_option_values
UPDATE `field_option_values` 
INNER JOIN `field_options` 
  ON `field_options`.`id` = `field_option_values`.`field_option_id`
    AND (`field_options`.`code` = 'StaffAbsenceReasons' OR `field_options`.`code` = 'StudentAbsenceReasons')
    AND `field_options`.`plugin` = 'FieldOption'
SET `field_option_values`.`visible` = 1;

UPDATE `field_options` SET `params`=NULL WHERE `code`='StaffAbsenceReasons' AND `plugin` = 'FieldOptions';
UPDATE `field_options` SET `params`=NULL WHERE `code`='StudentAbsenceReasons' AND `plugin` = 'FieldOptions';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2562';


-- POCOR-2609
-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2609';


-- POCOR-2603
DELETE FROM labels WHERE module = 'Accounts' AND field = 'password';
DELETE FROM labels WHERE module = 'Accounts' AND field = 'retype_password';
DELETE FROM labels WHERE module = 'StudentAccount' AND field = 'password';
DELETE FROM labels WHERE module = 'StudentAccount' AND field = 'retype_password';
DELETE FROM labels WHERE module = 'StaffAccount' AND field = 'password';
DELETE FROM labels WHERE module = 'StaffAccount' AND field = 'retype_password';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2603';


-- POCOR-2658
-- labels
UPDATE `labels` SET `field_name` = 'Area (Administrative)' WHERE `module` = 'Institutions' AND `field` = 'area_administrative_id';
UPDATE `labels` SET `field_name` = 'Area (Education)' WHERE `module` = 'Institutions' AND `field` = 'area_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2658';


-- 3.4.15
-- db_version
UPDATE config_items SET value = '3.4.15' WHERE code = 'db_version';
