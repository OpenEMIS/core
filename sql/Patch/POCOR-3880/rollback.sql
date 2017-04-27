DELETE FROM `authentication_type_attributes` WHERE `attribute_field` = 'role_mapping' OR `attribute_field` = 'saml_role_mapping';

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3880';
