-- custom_field_types
DELETE FROM `custom_field_types` WHERE `code` = 'FILE';

-- custom_modules
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,STUDENT_LIST' WHERE `model` = 'Institution.Institutions';
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE' WHERE `model` = 'Student.Students';
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE' WHERE `model` = 'Staff.Staff';
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE' WHERE `model` = 'Institution.InstitutionInfrastructures';

-- Restore tables
DROP TABLE IF EXISTS `custom_field_values`;
RENAME TABLE `z_2449_custom_field_values` TO `custom_field_values`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2449';
