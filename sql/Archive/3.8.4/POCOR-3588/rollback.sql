-- examination_items
DROP TABLE IF EXISTS `examination_items`;
RENAME TABLE `z_3588_examination_items` TO `examination_items`;

-- examination_item_results
DROP TABLE IF EXISTS `examination_item_results`;
RENAME TABLE `z_3588_examination_item_results` TO `examination_item_results`;

-- examination_centre_students
DROP TABLE IF EXISTS `examination_centre_students`;
RENAME TABLE `z_3588_examination_centre_students` TO `examination_centre_students`;

-- examination_centre_subjects
DROP TABLE IF EXISTS `examination_centre_subjects`;
RENAME TABLE `z_3588_examination_centre_subjects` TO `examination_centre_subjects`;

-- import_mapping
UPDATE `import_mapping`
SET `column_name` = 'education_subject_id', `description` = 'Code', `lookup_plugin` = 'Education', `lookup_model` = 'EducationSubjects', `lookup_column` = 'code'
WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'examination_item_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3588';
