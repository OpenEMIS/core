-- drop params column from xxx_custom_fields
ALTER TABLE `custom_fields` DROP `params`;
ALTER TABLE `institution_custom_fields` DROP `params`;
ALTER TABLE `student_custom_fields` DROP `params`;
ALTER TABLE `staff_custom_fields` DROP `params`;
ALTER TABLE `infrastructure_custom_fields` DROP `params`;
ALTER TABLE `survey_questions` DROP `params`;

-- drop params column from xxx_custom_forms_fields
ALTER TABLE `infrastructure_custom_forms_fields` DROP `section`;

-- custom_modules
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,STUDENT_LIST' WHERE `model` = 'Institution.Institutions';
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE' WHERE `model` = 'Student.Students';
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE' WHERE `model` = 'Staff.Staff';
UPDATE `custom_modules` SET `supported_field_types` = NULL, `visible` = 0 WHERE `model` = 'Institution.InstitutionInfrastructures';

-- custom_field_types
UPDATE `custom_field_types` SET `visible` = 0 WHERE `code` IN ('DATE', 'TIME');

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2445';
