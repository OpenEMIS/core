-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3567', NOW());

-- import_mapping
DELETE FROM `import_mapping` WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'institution_id';

UPDATE `import_mapping` SET `order` = 6 WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'marks';
UPDATE `import_mapping` SET `order` = 7 WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'examination_grading_option_id';
