-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3601', NOW());

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
('Institution.InstitutionTextbooks', 'textbook_id', NULL, 1, 2, 'Textbook', 'Textbooks', 'id'),
('Institution.InstitutionTextbooks', 'student_id', 'OpenEMIS ID', 2, 2, 'Security', 'Users', 'openemis_no'),
('Institution.InstitutionTextbooks', 'code', NULL, 3, 0, NULL, NULL, NULL),
('Institution.InstitutionTextbooks', 'textbook_status_id', 'Code', 4, 2, 'Textbook', 'TextbookStatuses', 'code'),
('Institution.InstitutionTextbooks', 'textbook_condition_id', 'Code', 5, 1, 'Textbook', 'TextbookConditions', 'national_code'),
('Institution.InstitutionTextbooks', 'comment', '(Optional)', 6, 0, NULL, NULL, NULL);

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1052, 'Import Textbooks', 'Institutions', 'Institutions', 'Academic', 8, NULL, NULL, NULL, NULL, 'ImportTextbooks.add|ImportTextbooks.template|ImportTextbooks.results|ImportTextbooks.downloadFailed|ImportTextbooks.downloadPassed', 1052, 1, NULL, NULL, NULL, 1, NOW());
