CREATE TABLE IF NOT EXISTS `db_patches` (
  `issue` varchar(15) NOT NULL,
  PRIMARY KEY (`issue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `db_patches` VALUES ('PHPOE-1815');

-- field_options
INSERT INTO `field_options` (`plugin`, `old_id`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Students', 0, 'StudentTransferReasons', 'Transfer Reasons', 'Student', NULL, 17, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- institution_student_transfers
ALTER TABLE `institution_student_transfers` ADD `student_transfer_reason_id` INT(11) NOT NULL AFTER `previous_institution_id`;
ALTER TABLE `institution_student_transfers` ADD `comment` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `student_transfer_reason_id`;

-- security_functions
UPDATE `security_functions` SET `_execute` = 'TransferRequests.add|TransferRequests.edit' WHERE `id` = 1020;
UPDATE `security_functions` SET `_execute` = 'TransferApprovals.edit' WHERE `id` = 1021;
