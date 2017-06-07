-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3823', NOW());

-- import_mapping
DELETE FROM `import_mapping` 
WHERE `model` = 'User.Users'
AND `column_name` = 'Identity';

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
VALUES (NULL, 'User.Users', 'nationality_id', 'Id (Optional)', '14', '2', 'FieldOption', 'Nationalities', 'id');

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
VALUES (NULL, 'User.Users', 'identity_type_id', 'Code (Optional)', '15', '1', 'FieldOption', 'IdentityTypes', 'national_code');

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
VALUES (NULL, 'User.Users', 'identity_number', '', '16', '0', NULL, NULL, NULL);