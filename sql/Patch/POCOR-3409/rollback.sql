-- config_items
UPDATE `config_items`
SET `value`='Local'
WHERE `id`='1001' AND `value`='OAuth2OpenIDConnect';

-- config_item_options
DELETE FROM `config_item_options` WHERE `option_type` = 'authentication_type' AND `value` = 'OAuth2OpenIDConnect';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3409';
