-- institution_student_admission
ALTER TABLE `institution_student_admission` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Approve, 2 -> Reject';

-- institution_student_dropout
ALTER TABLE `institution_student_dropout` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Approve, 2 -> Reject';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3440';