-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2445', NOW());

-- add params column
ALTER TABLE `custom_fields` ADD `params` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `is_unique`;
ALTER TABLE `institution_custom_fields` ADD `params` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `is_unique`;
ALTER TABLE `student_custom_fields` ADD `params` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `is_unique`;
ALTER TABLE `staff_custom_fields` ADD `params` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `is_unique`;
ALTER TABLE `infrastructure_custom_fields` ADD `params` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `is_unique`;
ALTER TABLE `survey_questions` ADD `params` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `is_unique`;
