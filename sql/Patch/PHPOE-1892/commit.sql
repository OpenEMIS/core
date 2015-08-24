INSERT INTO `db_patches` VALUES ('PHPOE-1892');

INSERT INTO `student_statuses` (`code`, `name`)
VALUES ('PENDING_ADMISSION', 'Pending Admission');

INSERT INTO `student_statuses` (`code`, `name`)
VALUES ('REJECTED', 'Rejected');

ALTER TABLE `institution_student_transfers` 
CHANGE COLUMN `security_user_id` `student_id` INT(11) NOT NULL COMMENT '' ,
ADD COLUMN `type` INT(1) NOT NULL DEFAULT 1 COMMENT '1 -> Transfers, 2 -> Admission' AFTER `comment`, RENAME TO  `institution_student_admission` ;


INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StudentAdmission', 'student_id', 'Student', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StudentAdmission', 'created', 'Date of Application', '1', NOW());