-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2298', NOW());

-- security_functions
CREATE TABLE `z_2298_security_functions` LIKE `security_functions`;

INSERT INTO `z_2298_security_functions` 
SELECT * FROM `security_functions` WHERE `id` = 1025;

UPDATE `security_functions` SET `name`='Surveys', `_view`='Surveys.index|Surveys.view', `_edit`='Surveys.edit', `_delete`=NULL, `_execute` = 'Surveys.excel' WHERE `id`=1025;