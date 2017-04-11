-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2016');

-- security_functions
UPDATE `security_functions` SET `_execute` = 'TransferRequests.add|TransferRequests.edit|Transfer.add' WHERE `id` = 1022;
