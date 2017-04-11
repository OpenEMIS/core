-- Using PHPOE-1902-2 as PHPOE-1902 has been previously used
-- db_patches
INSERT INTO db_patches VALUES('PHPOE-1902-2', NOW());

-- security_functions
UPDATE `security_functions` SET `_execute`='Promotion.index|Promotion.add' WHERE `id`='1005';

UPDATE `config_items` SET `value` = '3.3.5' WHERE `code` = 'db_version';
