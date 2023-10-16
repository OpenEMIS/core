INSERT INTO `db_patches` VALUES ('PHPOE-2088');

-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Assessments.view|Results.index' WHERE `id` = 1015;
