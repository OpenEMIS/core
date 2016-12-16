-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3661', NOW());

-- import_mapping
INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
(104, 'Examination.ExaminationCentreRooms', 'examination_id', 'Code', 1, 2, 'Examination', 'Examinations', 'code'),
(105, 'Examination.ExaminationCentreRooms', 'examination_centre_id', 'Code', 2, 2, 'Examination', 'ExaminationCentres', 'code'),
(106, 'Examination.ExaminationCentreRooms', 'name', '', 3, 0, NULL, NULL, NULL),
(107, 'Examination.ExaminationCentreRooms', 'size', '(Optional)', 4, 0, NULL, NULL, NULL),
(108, 'Examination.ExaminationCentreRooms', 'number_of_seats', '(Optional)', 5, 0, NULL, NULL, NULL);