-- POCOR-3880
DELETE FROM `authentication_type_attributes` WHERE `attribute_field` = 'role_mapping' OR `attribute_field` = 'saml_role_mapping';

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3880';


-- 3.9.12
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.12' WHERE code = 'db_version';
