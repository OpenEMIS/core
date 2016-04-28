INSERT INTO `db_patches` VALUES ('PHPOE-1420', NOW());

UPDATE `security_functions` SET `_execute`='Assessments.excel' WHERE `id`='1015';
