INSERT INTO `db_patches` VALUES ('PHPOE-1892');

INSERT INTO `student_statuses` (`code`, `name`)
VALUES ('PENDING_ADMISSION', 'Pending Admission');

INSERT INTO `student_statuses` (`code`, `name`)
VALUES ('REJECTED', 'Rejected');

ALTER TABLE `institution_student_transfers` 
ADD COLUMN `type` VARCHAR(100) NULL AFTER `comment`, 
RENAME TO  `institution_student_admission` ;

INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StudentAdmission', 'security_user_id', 'Student', '1', NOW());