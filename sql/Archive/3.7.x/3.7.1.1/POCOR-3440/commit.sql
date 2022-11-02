-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3440', NOW());

-- label
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('23a54720-95de-11e6-8c88-525400b263eb', 'UndoStudentStatus', 'student_status_id', 'Institution -> Students -> Undo Student Status', 'Undo', NULL, NULL, '1', NULL, NULL, '1', '2016-10-19 00:00:00');

-- institution_student_admission
ALTER TABLE `institution_student_admission` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Approve, 2 -> Reject, 3 -> Undo';

-- institution_student_dropout
ALTER TABLE `institution_student_dropout` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Approve, 2 -> Reject, 3 -> Undo';