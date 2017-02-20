-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3525', NOW());

UPDATE `import_mapping`
SET `lookup_plugin` = 'Institution', `lookup_model` = 'StudentUser'
WHERE `model` = 'Institution.Students' AND `column_name` = 'student_id';
