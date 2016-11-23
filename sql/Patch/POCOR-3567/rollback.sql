-- import_mapping
ALTER TABLE `import_mapping` CHANGE `foreign_key` `foreign_key` INT(11) NULL DEFAULT '0' COMMENT '0: not foreign key, 1: field options, 2: direct table, 3: non-table list';

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
('Examination.ExaminationItemResults', 'institution_id', 'Code (Leave as blank for private candidate)', 6, 2, 'Institution', 'Institutions', 'code');

UPDATE `import_mapping` SET `order` = 7 WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'marks';
UPDATE `import_mapping` SET `order` = 8 WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'examination_grading_option_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3567';
