-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3440', NOW());

-- institution_student_admission
ALTER TABLE `institution_student_admission` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Approve, 2 -> Reject, 3 -> Undo';

-- institution_student_dropout
ALTER TABLE `institution_student_dropout` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Approve, 2 -> Reject, 3 -> Undo';