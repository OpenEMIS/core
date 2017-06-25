DROP TABLE `system_authentications`;

DROP TABLE `authentication_types`;

DROP TABLE `idp_google`;

DROP TABLE `idp_oauth`;

DROP TABLE `idp_saml`;

ALTER TABLE `z_3931_authentication_type_attributes`
RENAME TO  `authentication_type_attributes` ;

-- config_items
DELETE FROM `config_items` WHERE `id` = 1001;

INSERT INTO `config_items`
SELECT * FROM z_3931_config_items;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3931';
