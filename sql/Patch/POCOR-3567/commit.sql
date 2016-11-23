-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3567', NOW());

-- import_mapping
ALTER TABLE `import_mapping` CHANGE `foreign_key` `foreign_key` INT(11) NULL DEFAULT '0' COMMENT '0: not foreign key, 1: field options, 2: direct table, 3: non-table list, 4: custom';

DELETE FROM `import_mapping` WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'institution_id';

UPDATE `import_mapping` SET `order` = 6 WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'marks';
UPDATE `import_mapping` SET `order` = 7 WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'examination_grading_option_id';
