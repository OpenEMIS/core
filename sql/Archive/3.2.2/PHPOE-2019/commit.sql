-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2019');

-- Institution Student dropout table
CREATE TABLE `institution_student_dropout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `effective_date` date NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Approve, 2 -> Reject',
  `institution_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `student_dropout_reason_id` int(11) NOT NULL,
  `comment` text,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- student_statuses
INSERT INTO `student_statuses` (`code`, `name`) 
VALUES ('PENDING_DROPOUT', 'Pending Dropout');

-- field_options
INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('Students', 'StudentDropoutReasons', 'Dropout Reasons', 'Student', NULL, 18, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- Dummy data for the student dropout reasons
INSERT INTO `field_option_values` (`name`, `order`, `visible`, `default`, `field_option_id`, `created_user_id`, `created`) 
VALUES ('Relocation', 1, 1, 1, (SELECT `field_options`.`id` FROM `field_options` WHERE `code` = 'StudentDropoutReasons'), 1, NOW());

-- Security function
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_execute`, `order`, `visible`, `created_user_id`, `created`) 
VALUES (1030, 'Dropout Request', 'Institutions', 'Institutions', 'Students', 1000,  'DropoutRequests.add|DropoutRequests.edit', 1030, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_execute`, `order`, `visible`, `created_user_id`, `created`) 
VALUES (1031, 'Student Dropout', 'Institutions', 'Institutions', 'Students', 1000, 'StudentDropout.index|StudentDropout.view', 'StudentDropout.edit|StudentDropout.view', 1031, 1, 1, NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StudentDropout', 'created', 'Institutions -> Student Dropout','Date of Application', 1, 1, NOW());

ALTER TABLE `security_group_users` ADD INDEX ( `security_group_id` ) ;
ALTER TABLE `security_group_users` ADD INDEX ( `security_user_id` ) ;
ALTER TABLE `security_group_users` ADD INDEX ( `security_role_id` ) ;
