INSERT INTO `db_patches` VALUES ('PHPOE-2124');

-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Assessments.view', `_edit` = 'Assessments.edit' WHERE `id` = 1015;
