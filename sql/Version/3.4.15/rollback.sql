-- POCOR-2445
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


-- POCOR-2446
-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2446';

UPDATE custom_field_types SET visible = 0 WHERE code = 'DATE';
UPDATE custom_field_types SET visible = 0 WHERE code = 'TIME';


-- POCOR-2608
UPDATE labels SET field = 'security_user_id' WHERE module = 'InstitutionSections' AND field = 'staff_id' AND module_name = 'Institutions -> Classes';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2608';


-- 3.4.14
UPDATE config_items SET value = '3.4.14' WHERE code = 'db_version';
