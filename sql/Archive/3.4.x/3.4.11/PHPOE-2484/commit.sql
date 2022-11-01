-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2484', NOW());

-- security_functions
UPDATE `security_functions` SET `_execute`='Promotion.index|Promotion.add|Promotion.reconfirm' WHERE `id`=1005;
