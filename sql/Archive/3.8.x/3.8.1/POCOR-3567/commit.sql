-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3567', NOW());

-- update import_mapping
ALTER TABLE `import_mapping` CHANGE `foreign_key` `foreign_key` INT(11) NULL DEFAULT '0' COMMENT '0: not foreign key, 1: field options, 2: direct table, 3: non-table list, 4: custom';

-- duplicate and backup table
CREATE TABLE IF NOT EXISTS `z_3567_import_mapping` LIKE `import_mapping`;
INSERT INTO `z_3567_import_mapping` SELECT * FROM `import_mapping`;

-- import_mapping
DELETE FROM `import_mapping` WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'academic_period_id';
DELETE FROM `import_mapping` WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'examination_centre_id';
DELETE FROM `import_mapping` WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'institution_id';

UPDATE `import_mapping` SET `description` = 'Id', `lookup_column` = 'id',  `order` = 1 WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'examination_id';
UPDATE `import_mapping` SET `order` = 2 WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'education_subject_id';
UPDATE `import_mapping` SET `order` = 3 WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'student_id';
UPDATE `import_mapping` SET `order` = 4 WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'marks';
UPDATE `import_mapping` SET `order` = 5 WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'examination_grading_option_id';
