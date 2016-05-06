-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2445', NOW());

-- add params column to xxx_custom_fields
ALTER TABLE `custom_fields` ADD `params` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `is_unique`;
ALTER TABLE `institution_custom_fields` ADD `params` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `is_unique`;
ALTER TABLE `student_custom_fields` ADD `params` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `is_unique`;
ALTER TABLE `staff_custom_fields` ADD `params` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `is_unique`;
ALTER TABLE `infrastructure_custom_fields` ADD `params` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `is_unique`;
ALTER TABLE `survey_questions` ADD `params` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `is_unique`;

-- add section column to xxx_custom_forms_fields
ALTER TABLE `infrastructure_custom_forms_fields` ADD `section` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `infrastructure_custom_field_id`;

-- custom_modules
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,DATE,TIME,STUDENT_LIST' WHERE `model` = 'Institution.Institutions';
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,DATE,TIME' WHERE `model` = 'Student.Students';
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,DATE,TIME' WHERE `model` = 'Staff.Staff';
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,DATE,TIME', `visible` = 1 WHERE `model` = 'Institution.InstitutionInfrastructures';

-- custom_field_types
UPDATE `custom_field_types` SET `visible` = 1 WHERE `code` IN ('DATE', 'TIME');
