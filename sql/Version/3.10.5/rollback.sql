-- POCOR-3931
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

DROP TABLE z_3931_config_items;

-- security_user_logins
DROP TABLE security_user_logins;

ALTER TABLE `z_3931_security_user_logins`
RENAME TO  `security_user_logins` ;

DELETE FROM `security_functions` WHERE `id` IN (5073, 5074, 5075, 5076);

UPDATE `security_functions` SET `order` = `order` - 5 WHERE `order` > 5021 AND `order` < 6000;

DELETE FROM `security_role_functions` WHERE `security_function_id` IN (5073, 5074, 5075, 5076);

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3931';


-- POCOR-2470
-- reports
DROP TABLE IF EXISTS `reports`;

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 6012;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-2470';


-- POCOR-3809
-- security_users
DROP TABLE IF EXISTS `security_users`;
RENAME TABLE `z_3809_security_users` TO `security_users`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3809';


-- POCOR-3955
-- security_functions
DROP TABLE IF EXISTS `security_functions`;
RENAME TABLE `z_3955_security_functions` TO `security_functions`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3955';


-- 3.10.4
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.10.4' WHERE code = 'db_version';
