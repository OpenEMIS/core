-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3886', NOW());

-- staff_employments
ALTER TABLE `staff_employments`  
ADD `file_name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL  AFTER `comment`,  
ADD `file_content` LONGBLOB NULL DEFAULT NULL  AFTER `file_name`;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('cdf0fca9-0a07-11e7-b9c5-525400b263eb', 'Employments', 'file_content', 'Staff > Employments', 'Attachment', NULL, NULL, 1, NULL, NULL, 1, '2017-03-16 00:00:00');

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Employments.download' WHERE `id` = 3019;
UPDATE `security_functions` SET `_execute` = 'StaffEmployments.download' WHERE `id` = 7020;