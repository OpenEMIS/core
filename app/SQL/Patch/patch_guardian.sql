CREATE TABLE `guardians` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `preferred_name` varchar(100) DEFAULT NULL,
  `gender` varchar(1) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `home_phone` varchar(50) DEFAULT NULL,
  `office_phone` varchar(50) DEFAULT NULL,
  `mobile_phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `post_code` varchar(20) DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `comments` text,
  `guardian_education_level_id` int(11) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `first_name` (`first_name`,`middle_name`,`last_name`,`preferred_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `guardian_education_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL,
  `international_code` varchar(10) DEFAULT NULL,
  `national_code` varchar(10) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

--
-- Table structure for table `guardian_relations`
--

CREATE TABLE `guardian_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL,
  `international_code` varchar(10) DEFAULT NULL,
  `national_code` varchar(10) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `student_guardians` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `guardian_id` int(11) NOT NULL,
  `guardian_relation_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `guardian_id` (`guardian_id`),
  KEY `guardian_relation_id` (`guardian_relation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

