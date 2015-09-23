INSERT INTO `db_patches` VALUES ('PHPOE-2124');

-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Assessments.view', `_edit` = 'Assessments.edit' WHERE `id` = 1015;

-- assessment_item_results
ALTER TABLE `assessment_item_results` CHANGE `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
