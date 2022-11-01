INSERT INTO `db_patches` VALUES ('PHPOE-2124');

-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Assessments.view', `_edit` = 'Assessments.edit' WHERE `id` = 1015;

-- assessment_item_results
ALTER TABLE `assessment_item_results` CHANGE `id` `id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `assessment_item_results` CHANGE `marks` `marks` INT(5) NULL DEFAULT NULL;
ALTER TABLE `assessment_item_results` CHANGE `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `assessment_item_results` CHANGE `institution_site_id` `institution_id` INT(11) NOT NULL;
