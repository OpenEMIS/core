UPDATE `import_mapping`
SET `lookup_plugin` = 'Student', `lookup_model` = 'Students'
WHERE `model` = 'Institution.Students' AND `column_name` = 'student_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3525';
