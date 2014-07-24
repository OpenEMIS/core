DROP TABLE IF EXISTS `field_option_values`;
CREATE TABLE IF NOT EXISTS `field_option_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(10) DEFAULT NULL,
  `national_code` varchar(10) DEFAULT NULL,
  `field_option_id` int(5) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `field_option_id` (`field_option_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Dumping data for table `field_option_values`
--

INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'Government', 0, 1, 1, 0, 'government', 'government', 1, NULL, NULL, 1, '2014-05-12 09:35:21'),
(2, 'Test Sector', 0, 1, 1, 0, 'test-secto', 'test-secto', 2, NULL, NULL, 1, '2014-05-12 09:36:18'),
(3, 'Full-Time', 0, 1, 1, 1, '', '', 65, NULL, NULL, 1, '2014-06-04 16:54:58'),
(4, 'Part-Time', 0, 1, 1, 0, '', '', 65, NULL, NULL, 1, '2014-06-04 17:09:17'),
(5, 'Contract', 0, 1, 1, 0, '', '', 65, NULL, NULL, 1, '2014-06-04 17:09:25'),
(6, 'Boys', 0, 1, 1, 0, '', '', 66, NULL, NULL, 1, '2014-06-05 10:48:13'),
(7, 'Girls', 0, 1, 1, 0, '', '', 66, NULL, NULL, 1, '2014-06-05 10:48:18'),
(8, 'Mixed', 0, 1, 1, 0, '', '', 66, NULL, NULL, 1, '2014-06-05 10:48:24'),
(9, 'Math', 1, 1, 1, 0, NULL, NULL, 67, NULL, NULL, 1, '2014-06-11 22:20:47'),
(10, 'Science', 2, 1, 1, 0, NULL, NULL, 67, NULL, NULL, 1, '2014-06-11 22:20:47'),
(11, 'Arts', 3, 1, 1, 0, NULL, NULL, 67, NULL, NULL, 1, '2014-06-11 22:20:47'),
(12, 'Exam', 0, 1, 1, 0, '', '', 68, NULL, NULL, 1, '2014-05-09 17:21:29'),
(13, 'Practical', 0, 1, 1, 0, '', '', 68, NULL, NULL, 1, '2014-05-09 17:21:35'),
(14, 'Attendance', 0, 1, 1, 0, '', '', 68, NULL, NULL, 1, '2014-05-09 17:21:46'),
(17, 'School Based Study', 1, 1, 1, 0, NULL, NULL, 69, NULL, NULL, 1, '2014-06-17 00:00:00'),
(18, 'Self Based Study', 2, 1, 1, 0, NULL, NULL, 69, NULL, NULL, 1, '2014-06-17 00:00:00'),
(NULL, 'Sick Leave', 0, 1, 1, 0, NULL, NULL, 73, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Pending', 0, 1, 1, 0, NULL, NULL, 74, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Approved', 0, 1, 1, 0, NULL, NULL, 74, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Rejected', 0, 1, 1, 0, NULL, NULL, 74, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Cancelled', 0, 1, 1, 0, NULL, NULL, 74, NULL, NULL, 1, '0000-00-00 00:00:00');
