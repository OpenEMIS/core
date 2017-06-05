-- `db_patches`
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3996', NOW());

-- `import_mapping`
INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
VALUES
('Training.TrainingSessionsTrainees', 'identity_number', '', 3, 0, NULL, NULL, NULL),
('Training.TrainingSessionsTrainees', 'identity_type_id', 'Code (Optional)', 2, 1, 'FieldOption', 'IdentityTypes', 'national_code'),
('Training.TrainingSessionsTrainees', 'openemis_no', '(Optional)', 1, 0, NULL, NULL, NULL);
