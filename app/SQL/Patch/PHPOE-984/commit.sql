INSERT INTO `config_items` (`id`, `name`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
(NULL, 'alert_frequency', 'Alerts', 'Frequency', 1, 1, 1, 1, '', '', 1, '0000-00-00 00:00:00'),
(NULL, 'alert_retry', 'Alerts', 'Retry', 0, 0, 1, 1, '', '', 1, '0000-00-00 00:00:00');

UPDATE `navigations` SET `title` = 'Questions' WHERE `module` LIKE 'Administration' AND `header` LIKE 'SMS' AND `title` LIKE 'Messages';
UPDATE `navigations` SET `header` = 'Communications' WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Sms' AND `header` LIKE 'SMS';

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_estonian_ci NOT NULL,
  `threshold` int(5) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '1',
  `method` varchar(50) COLLATE utf8_estonian_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8_estonian_ci NOT NULL,
  `message` text COLLATE utf8_estonian_ci NOT NULL,
  `modified_user_id` int(5) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(5) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_estonian_ci AUTO_INCREMENT=1 ;

--
-- new menu item Alerts to navigations
--

SET @commuQuestionsOrderId := 0;
SELECT `order` INTO @commuQuestionsOrderId FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Communications' AND `title` LIKE 'Questions';

UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` >= @commuQuestionsOrderId;

INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `created_user_id`, `created`) VALUES
(NULL, 'Administration', 'Alerts', 'Alerts', 'Communications', 'Alerts', 'Alert', 'Alert|Alert.add|Alert.view|Alert.edit', NULL, 33, 0, @commuQuestionsOrderId, 1, 1, '0000-00-00 00:00:00');

--
-- Table structure for table `alert_roles`
--

CREATE TABLE `alert_roles` (
  `id` char(36) NOT NULL,
  `alert_id` int(11) NOT NULL,
  `security_role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `alert_id` (`alert_id`),
  KEY `security_role_id` (`security_role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `alert_logs`
--

CREATE TABLE `alert_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `method` varchar(20) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `type` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


--
-- Table structure for table `system_processes`
--

CREATE TABLE `system_processes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `process_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `ended_user_id` int(11) DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`,`process_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


