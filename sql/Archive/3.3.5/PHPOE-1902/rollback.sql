-- security_functions
UPDATE `security_functions` SET `_execute`='Promotion.index|Promotion.indexEdit' WHERE `id`='1005';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1902-2';