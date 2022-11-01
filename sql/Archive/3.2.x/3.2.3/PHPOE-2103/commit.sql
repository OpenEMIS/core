INSERT INTO `db_patches` VALUES ('PHPOE-2103');

-- staff_leave_attachments
DROP TABLE IF EXISTS `staff_leave_attachments`;

-- staff_leaves
ALTER TABLE `staff_leaves` ADD `file_name` VARCHAR(250) NULL AFTER `number_of_days`, ADD `file_content` LONGBLOB NULL AFTER `file_name`;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'Leaves', 'file_content', 'Staff -> Career -> Leave','Attachment', 1, 1, NOW());

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Leaves.download' WHERE `id` = 3016;
