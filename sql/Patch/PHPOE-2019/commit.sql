-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2019');

-- field_options
INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Students', 'StudentDropoutReasons', 'Dropout Reasons', 'Student', NULL, 18, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- Dummy data for the student dropout reasons
INSERT INTO `field_option_values` (`name`, `order`, `visible`, `default`, `field_option_id`, `created_user_id`, `created`) VALUES
('Relocation', 1, 1, 1, (SELECT `field_options`.`id` FROM `field_options` WHERE `code` = 'StudentDropoutReasons'), 1, NOW());

-- Security function
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_execute`, `order`, `visible`, `created_user_id`, `created`) 
VALUES (1030, 'Dropout Request', 'Institutions', 'Institutions', 'Students', 1000,  'DropoutRequests.add|DropoutRequests.edit', 1030, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_execute`, `order`, `visible`, `created_user_id`, `created`) 
VALUES (1031, 'Dropout Approval', 'Institutions', 'Institutions', 'Students', 1000, 'DropoutApprovals.view', 'DropoutApprovals.edit|DropoutApprovals.view', 1031, 1, 1, NOW());