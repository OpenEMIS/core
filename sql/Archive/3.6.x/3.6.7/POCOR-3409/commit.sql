-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3409', NOW());

-- config_item_options
ALTER TABLE `config_item_options`
CHANGE COLUMN `id` `id` INT(11) NOT NULL COMMENT '' ;

INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`) VALUES (37, 'authentication_type', 'OAuth 2.0 with OpenID Connect', 'OAuth2OpenIDConnect', 5, 1);
