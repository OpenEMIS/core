-- POCOR-3198
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3198', NOW());

-- import_mapping
UPDATE `import_mapping`
SET `order` = '6'
WHERE `model` = 'Institution.StaffAbsences'
AND `column_name` = 'staff_absence_reason_id';

UPDATE `import_mapping`
SET `order` = '6'
WHERE `model` = 'Institution.InstitutionStudentAbsences'
AND `column_name` = 'student_absence_reason_id';

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
VALUES (87, 'Institution.StaffAbsences', 'absence_type_id', 'Code', '5', '2', 'Institution', 'AbsenceTypes', 'code');

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
VALUES (88, 'Institution.InstitutionStudentAbsences', 'absence_type_id', 'Code', '5', '2', 'Institution', 'AbsenceTypes', 'code');


-- 3.5.12
UPDATE config_items SET value = '3.5.12' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
