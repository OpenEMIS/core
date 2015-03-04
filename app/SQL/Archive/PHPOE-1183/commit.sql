DROP TABLE IF EXISTS `institution_site_activities`;
CREATE TABLE IF NOT EXISTS `institution_site_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(200) NOT NULL,
  `model_reference` int(11) NOT NULL,
  `field` varchar(200) NOT NULL,
  `old_value` varchar(255) NOT NULL,
  `new_value` varchar(255) NOT NULL,
  `operation` varchar(10) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `institution_site_id` (`institution_site_id`),
  INDEX `model_reference` (`model_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `staff_activities`
--

DROP TABLE IF EXISTS `staff_activities`;
CREATE TABLE IF NOT EXISTS `staff_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(200) NOT NULL,
  `model_reference` int(11) NOT NULL,
  `field` varchar(200) NOT NULL,
  `old_value` varchar(255) NOT NULL,
  `new_value` varchar(255) NOT NULL,
  `operation` varchar(10) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `staff_id` (`staff_id`),
  INDEX `model_reference` (`model_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `student_activities`
--

DROP TABLE IF EXISTS `student_activities`;
CREATE TABLE IF NOT EXISTS `student_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(200) NOT NULL,
  `model_reference` int(11) NOT NULL,
  `field` varchar(200) NOT NULL,
  `old_value` varchar(255) NOT NULL,
  `new_value` varchar(255) NOT NULL,
  `operation` varchar(10) NOT NULL,
  `student_id` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `student_id` (`student_id`),
  INDEX `model_reference` (`model_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
