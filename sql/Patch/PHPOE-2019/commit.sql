-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2019');

-- field_options
INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Students', 'StudentDropoutReasons', 'Dropout Reasons', 'Student', NULL, 18, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- Dummy data for the student dropout reasons
INSERT INTO `field_option_values` (`name`, `order`, `visible`, `default`, `field_option_id`, `created_user_id`, `created`) VALUES
('Relocation', 1, 1, 1, (SELECT `field_options`.`id` FROM `field_options` WHERE `code` = 'StudentDropoutReasons'), 1, NOW());