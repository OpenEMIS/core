-- import_mapping
INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
VALUES(80, 'User.Users', 'Identity', 'Number', 14, 4, 'User', 'Identities', 'FieldOption.IdentityTypes');

DELETE FROM `import_mapping` 
WHERE `model` = 'User.Users'
AND `column_name` IN ('nationality_id', 'identity_type_id', 'identity_number');

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3823';
