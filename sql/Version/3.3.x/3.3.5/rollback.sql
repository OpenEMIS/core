-- security_functions
UPDATE `security_functions` SET `_execute`='Promotion.index|Promotion.indexEdit' WHERE `id`='1005';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1902-2';

UPDATE `config_items` SET `value` = '3.3.4' WHERE `code` = 'db_version';
