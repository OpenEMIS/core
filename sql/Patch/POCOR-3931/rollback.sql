DROP TABLE `system_authentications`;

DROP TABLE `authentication_types`;

DROP TABLE `idp_google`;

DROP TABLE `idp_oauth`;

DROP TABLE `idp_saml`;

ALTER TABLE `z_3931_authentication_type_attributes`
RENAME TO  `authentication_type_attributes` ;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3931';
