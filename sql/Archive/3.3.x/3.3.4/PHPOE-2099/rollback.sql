-- config_items
DELETE FROM `config_items` WHERE `code` = 'support_url';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2099';
