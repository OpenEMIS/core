-- security_functions
UPDATE `security_functions` SET `_execute`='Promotion.index|Promotion.add' WHERE `id`=1005;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2484';
