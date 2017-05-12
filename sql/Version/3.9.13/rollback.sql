-- POCOR-3801
-- security_functions
UPDATE `security_functions` SET `_view` = 'Students.index|Students.view|StudentSurveys.index|StudentSurveys.view' WHERE `id` = 1012;

DELETE FROM `security_functions` WHERE `id` = 2033;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3801';


-- POCOR-3936
-- staff_behaviours
DROP TABLE IF EXISTS `staff_behaviours`;
RENAME TABLE `z_3936_staff_behaviours` TO `staff_behaviours`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3936';


-- POCOR-3880
DELETE FROM `authentication_type_attributes` WHERE `attribute_field` = 'role_mapping' OR `attribute_field` = 'saml_role_mapping';

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3880';


-- 3.9.12
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.12' WHERE code = 'db_version';
