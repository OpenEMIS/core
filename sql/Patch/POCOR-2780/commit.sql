--
-- POCOR-2780
-- 

INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2780', NOW());

CREATE TABLE `z_2780_import_mapping` LIKE `import_mapping`;
INSERT INTO `z_2780_import_mapping` 
SELECT *
FROM `import_mapping`;

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
(80, 'Institution.Staff', 'institution_position_id', 'Code', 1, 2, 'Institution', 'InstitutionPositions', 'position_no'),
(81, 'Institution.Staff', 'start_date', '( DD/MM/YYYY )', 2, 0, NULL, NULL, NULL),
(82, 'Institution.Staff', 'position_type', 'Code (Optional)', 3, 3, NULL, 'PositionTypes', 'id'),
(83, 'Institution.Staff', 'FTE', '(Not Required if Position Type is Full Time)', 4, 3, NULL, 'FTE', 'value'),
(84, 'Institution.Staff', 'staff_type_id', 'Code', 5, 1, 'FieldOption', 'StaffTypes', 'code'),
(85, 'Institution.Staff', 'staff_id', 'OpenEMIS ID', 6, 2, 'Staff', 'Staff', 'openemis_no')
;

