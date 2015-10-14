INSERT INTO `db_patches` VALUES ('PHPOE-1892');

INSERT INTO `student_statuses` (`code`, `name`)
VALUES ('PENDING_ADMISSION', 'Pending Admission');

INSERT INTO `student_statuses` (`code`, `name`)
VALUES ('REJECTED', 'Rejected');

ALTER TABLE `institution_student_transfers` 
CHANGE COLUMN `security_user_id` `student_id` INT(11) NOT NULL COMMENT '' ,
ADD COLUMN `type` INT(1) NOT NULL DEFAULT 2 COMMENT '1 -> Admission, 2 -> Transfer' AFTER `comment`, RENAME TO  `institution_student_admission` ;

UPDATE `security_functions` SET `controller`='Institutions', `_view`='TransferApprovals.view', `_execute`='TransferApprovals.edit|TransferApprovals.view' WHERE `name`='Transfer Approval';
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_execute`, `order`, `visible`, `created_user_id`, `created`) 
VALUES (1028, 'Student Admission', 'Institutions', 'Institutions', 'Students', 1000, 'StudentAdmission.index|StudentAdmission.view', 'StudentAdmission.edit|StudentAdmission.view', 1024, 1, 1, NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StudentAdmission', 'created', 'Institutions -> Student Admission','Date of Application', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'TransferApprovals', 'created', 'Institutions -> Transfer Approvals','Date of Application', 1, 1, NOW());