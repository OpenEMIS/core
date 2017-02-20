--
-- POCOR-2780
-- 

INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2780', NOW());

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
(81, 'Institution.Staff', 'institution_position_id', 'Code', 1, 2, 'Institution', 'InstitutionPositions', 'position_no'),
(82, 'Institution.Staff', 'start_date', '( DD/MM/YYYY )', 2, 0, NULL, NULL, NULL),
(83, 'Institution.Staff', 'position_type', 'Code', 3, 3, NULL, 'PositionTypes', 'id'),
(84, 'Institution.Staff', 'FTE', '(Not Required if Position Type is Full Time)', 4, 3, NULL, 'FTE', 'value'),
(85, 'Institution.Staff', 'staff_type_id', 'Code', 5, 1, 'FieldOption', 'StaffTypes', 'code'),
(86, 'Institution.Staff', 'staff_id', 'OpenEMIS ID', 6, 2, 'Staff', 'Staff', 'openemis_no')
;

DELETE FROM `security_functions` WHERE `id` = 7047;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1042, 'Import Staff', 'Institutions', 'Institutions', 'Staff', 1016, NULL, NULL, NULL, NULL, 'ImportStaff.add|ImportStaff.template|ImportStaff.results|ImportStaff.downloadFailed|ImportStaff.downloadPassed', 1042, 1, NULL, NULL, 1, NOW());
