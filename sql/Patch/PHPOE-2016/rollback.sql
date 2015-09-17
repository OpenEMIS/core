-- security_functions
UPDATE `security_functions` SET `_execute` = 'TransferRequests.add|TransferRequests.edit' WHERE `id` = 1022;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2016';
