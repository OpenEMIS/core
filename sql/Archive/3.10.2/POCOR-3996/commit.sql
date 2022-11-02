-- `db_patches`
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3996', NOW());

-- `import_mapping`
INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
VALUES
('Training.TrainingSessionsTrainees', 'identity_number', '', 3, 0, NULL, NULL, NULL),
('Training.TrainingSessionsTrainees', 'identity_type_id', 'Code (Optional)', 2, 1, 'FieldOption', 'IdentityTypes', 'national_code'),
('Training.TrainingSessionsTrainees', 'openemis_no', '(Optional)', 1, 0, NULL, NULL, NULL);

-- `labels`
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('6c3d2497-4b27-11e7-9846-525400b263eb', 'TrainingSessionsTrainees', 'openemis_no', 'Administration > Training > Sessions > Trainees', 'OpenEMIS ID', NULL, NULL, '1', NULL, NULL, '1', '2017-06-07 00:00:00');

-- `security_functions`
UPDATE `security_functions` SET `_execute` = 'ImportTrainees.add|ImportTrainees.template|ImportTrainees.results|ImportTrainees.downloadFailed|ImportTrainees.downloadPassed' 
WHERE `id` = 5040;
