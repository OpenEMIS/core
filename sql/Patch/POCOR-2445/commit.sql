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
ALTER TABLE `custom_forms_fields` ADD `section` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `custom_field_id`;
ALTER TABLE `institution_custom_forms_fields` ADD `section` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `institution_custom_field_id`;
ALTER TABLE `student_custom_forms_fields` ADD `section` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `student_custom_field_id`;
ALTER TABLE `staff_custom_forms_fields` ADD `section` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `staff_custom_field_id`;
ALTER TABLE `infrastructure_custom_forms_fields` ADD `section` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `infrastructure_custom_field_id`;
