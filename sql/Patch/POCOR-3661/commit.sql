-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3661', NOW());

-- import_mapping
INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
(104, 'Examination.ExaminationCentreRooms', 'examination_id', 'Code', 1, 2, 'Examination', 'Examinations', 'code'),
(105, 'Examination.ExaminationCentreRooms', 'examination_centre_id', 'ID', 2, 2, 'Examination', 'ExaminationCentres', 'id'),
(106, 'Examination.ExaminationCentreRooms', 'name', '', 3, 0, NULL, NULL, NULL),
(107, 'Examination.ExaminationCentreRooms', 'size', '(Optional)', 4, 0, NULL, NULL, NULL),
(108, 'Examination.ExaminationCentreRooms', 'number_of_seats', '(Optional)', 5, 0, NULL, NULL, NULL);

-- examination_centre_rooms
ALTER TABLE `examination_centre_rooms` CHANGE `size` `size` FLOAT NULL DEFAULT '0';
ALTER TABLE `examination_centre_rooms` CHANGE `number_of_seats` `number_of_seats` INT(3) NULL DEFAULT '0';

-- security_functions
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('5057', 'Import Examination Rooms', 'Examinations', 'Administration', 'Examinations', '5000', NULL, NULL, NULL, NULL, 'ImportExaminationCentreRooms.add|ImportExaminationCentreRooms.template|ImportExaminationCentreRooms.results|ImportExaminationCentreRooms.downloadFailed|ImportExaminationCentreRooms.downloadPassed', '5053', '1', NULL, NULL, NULL, '1', '2016-11-18 09:51:29');