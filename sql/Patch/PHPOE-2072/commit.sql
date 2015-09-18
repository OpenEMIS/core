-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2072');

-- security_function
UPDATE `security_functions` SET `_view`='TransferRequests.index|TransferRequests.view', `_delete` = 'TransferRequests.remove' WHERE `id`='1022';

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'TransferRequests', 'created', 'Institutions -> Transfer Requests','Date of Application', 1, 1, NOW());

-- student_statuses
DELETE FROM `student_statuses` WHERE `code`='REJECTED';