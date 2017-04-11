-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3198';

-- import_mapping
DELETE FROM `import_mapping`
WHERE `model` = 'Institution.StaffAbsences'
AND `column_name` = 'absence_type_id';

DELETE FROM `import_mapping`
WHERE `model` = 'Institution.InstitutionStudentAbsences'
AND `column_name` = 'absence_type_id';