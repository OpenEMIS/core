-- POCOR-3823
-- import_mapping
INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
VALUES(80, 'User.Users', 'Identity', 'Number', 14, 4, 'User', 'Identities', 'FieldOption.IdentityTypes');

DELETE FROM `import_mapping`
WHERE `model` = 'User.Users'
AND `column_name` IN ('nationality_id', 'identity_type_id', 'identity_number');

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3823';


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
