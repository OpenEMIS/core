--
-- POCOR-2780
-- 

INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2780', NOW());

CREATE TABLE `z_2780_import_mapping` LIKE `import_mapping`;
INSERT INTO `z_2780_import_mapping` 
SELECT *
FROM `import_mapping`;

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
('Institution.Staff', 'institution_position_id', 'Code', 1, 2, 'Institution', 'InstitutionPositions', 'position_no'),
('Institution.Staff', 'start_date', '( DD/MM/YYYY )', 2, 0, NULL, NULL, NULL),
('Institution.Staff', 'position_type', 'Code (Optional)', 3, 3, NULL, 'PositionTypes', 'id'),
('Institution.Staff', 'FTE', '(Not Required if Position Type is Full Time)', 4, 3, NULL, 'FTE', 'value'),
('Institution.Staff', 'staff_type_id', 'Code', 5, 1, 'FieldOption', 'StaffTypes', 'code'),
('Institution.Staff', 'staff_id', 'OpenEMIS ID', 6, 2, 'Staff', 'Staff', 'openemis_no')
;

CREATE TABLE `z_2780_security_functions` LIKE `security_functions`;
INSERT INTO `z_2780_security_functions` 
SELECT *
FROM `security_functions`;

INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Import Staff', 'Institutions', 'Institutions', 'Staff', 1016, NULL, NULL, NULL, NULL, 'ImportStaff.add|ImportStaff.template|ImportStaff.results|ImportStaff.downloadFailed|ImportStaff.downloadPassed', 1042, 1, NULL, NULL, 1, NOW());

