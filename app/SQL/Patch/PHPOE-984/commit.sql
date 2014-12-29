INSERT INTO `config_items` (`id`, `name`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
(NULL, 'alert_frequency', 'Alerts', 'Frequency', 1, 1, 1, 0, '', '', 1, '0000-00-00 00:00:00'),
(NULL, 'alert_retry', 'Alerts', 'Retry', 0, 0, 1, 0, '', '', 1, '0000-00-00 00:00:00');

UPDATE `navigations` SET `title` = 'Questions' WHERE `module` LIKE 'Administration' AND `header` LIKE 'SMS' AND `title` LIKE 'Messages';
UPDATE `navigations` SET `header` = 'Communications' WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Sms' AND `header` LIKE 'SMS';

--
-- Table structure for table `alerts`
--

DROP TABLE IF EXISTS `alerts`;
CREATE TABLE `alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `threshold` int(5) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '1',
  `method` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `modified_user_id` int(5) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(5) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `alerts`
--

INSERT INTO `alerts` (`id`, `code`, `name`, `threshold`, `status`, `method`, `subject`, `message`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'Attendance', 'Student Absent', 14, 1, 'Email', 'OpenEMIS Alert', 'Student absent 14 days.', NULL, NULL, 1, '0000-00-00 00:00:00');


--
-- new menu item Alerts to navigations
--

SET @commuQuestionsOrderId := 0;
SELECT `order` INTO @commuQuestionsOrderId FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Communications' AND `title` LIKE 'Questions';

UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` >= @commuQuestionsOrderId;

INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `created_user_id`, `created`) VALUES
(NULL, 'Administration', 'Alerts', 'Alerts', 'Communications', 'Alerts', 'index', 'index|view|edit', NULL, 33, 0, @commuQuestionsOrderId, 1, 1, '0000-00-00 00:00:00');

--
-- Table structure for table `alert_roles`
--

DROP TABLE IF EXISTS `alert_roles`;
CREATE TABLE `alert_roles` (
  `id` char(36) NOT NULL,
  `alert_id` int(11) NOT NULL,
  `security_role_id` int(11) NOT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alert_id` (`alert_id`),
  KEY `security_role_id` (`security_role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `alert_logs`
--

DROP TABLE IF EXISTS `alert_logs`;
CREATE TABLE `alert_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `method` varchar(20) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `type` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL COMMENT '-1 -> Failed, 0 -> Pending, 1 -> Success',
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `security_user_id` (`created_user_id`),
  KEY `method` (`method`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


--
-- Table structure for table `system_processes`
--

DROP TABLE IF EXISTS `system_processes`;
CREATE TABLE `system_processes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `process_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `ended_user_id` int(11) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`,`process_id`,`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


--
-- part 2
--

UPDATE `security_functions` SET `name` = 'Questions' WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Sms'  AND `name` LIKE 'Messages';

UPDATE `security_functions` SET `category` = 'Communications' WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Sms' AND `category` LIKE 'SMS';

SET @firstItemOrder := 0;
SELECT `order` INTO @firstItemOrder FROM `security_functions` WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Sms'  AND `name` LIKE 'Questions';

UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= @firstItemOrder;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Alerts', 'Alerts', 'Administration', 'Communications', 129, 'index|view', '_view:edit', NULL, NULL, NULL, @firstItemOrder, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

