-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3409', NOW());

-- config_item_options
INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) VALUES ('authentication_type', 'OAuth 2.0 with OpenID Connect', 'OAuth2OpenIDConnect', 5, 1);
