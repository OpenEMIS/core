-- POCOR-2445
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


-- POCOR-2446
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2446', NOW());

UPDATE custom_field_types SET visible = 1 WHERE code = 'DATE';
UPDATE custom_field_types SET visible = 1 WHERE code = 'TIME';


-- POCOR-2601
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2601', NOW());

-- security_users

UPDATE `security_users`
SET `address_area_id` = NULL
WHERE `security_users`.`address_area_id` NOT IN (SELECT id FROM area_administratives);

UPDATE `security_users`
SET `birthplace_area_id` = NULL
WHERE `security_users`.`birthplace_area_id` NOT IN (SELECT id FROM area_administratives);

-- institutions

UPDATE `institutions`
SET area_id = (SELECT id FROM areas WHERE parent_id = -1 LIMIT 1)
WHERE area_id NOT IN (SELECT id FROM areas);

UPDATE `institutions`
SET area_administrative_id = NULL
WHERE area_administrative_id NOT IN (SELECT id FROM area_administratives);

-- security_group_areas
DELETE FROM `security_group_areas`
WHERE area_id NOT IN (SELECT id FROM areas);


-- POCOR-2608
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2608', NOW());

UPDATE labels SET field = 'staff_id' WHERE module = 'InstitutionSections' AND field = 'security_user_id' AND module_name = 'Institutions -> Classes';


-- 3.4.15
-- db_version
UPDATE config_items SET value = '3.4.15' WHERE code = 'db_version';
