--
-- 1. chnage table students
--

RENAME TABLE `students` TO `students_bak` ;

CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identification_no` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `preferred_name` varchar(100) DEFAULT NULL,
  `gender` char(1) NOT NULL COMMENT 'M for Male, F for Female',
  `date_of_birth` date NOT NULL,
  `date_of_death` date DEFAULT NULL,
  `address` text,
  `address_area_id` int(11) DEFAULT '0',
  `birthplace_area_id` int(11) DEFAULT '0',
  `postal_code` varchar(20) DEFAULT NULL,
  `photo_name` varchar(250) DEFAULT '',
  `photo_content` longblob,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `first_name` (`first_name`),
  KEY `last_name` (`last_name`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`),
  KEY `created` (`created`),
  KEY `birthplace_area_id` (`birthplace_area_id`),
  KEY `address_area_id` (`address_area_id`),
  KEY `middle_name` (`middle_name`),
  KEY `identification_no` (`identification_no`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

INSERT INTO `students` (`id`, `identification_no`, `first_name`, `middle_name`, `last_name`, `preferred_name`, `gender`, `date_of_birth`, `date_of_death`, `address`, `address_area_id`, `birthplace_area_id`, `postal_code`, `photo_name`, `photo_content`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT `id`, `identification_no`, `first_name`, `middle_name`, `last_name`, `preferred_name`, `gender`, `date_of_birth`, `date_of_death`, `address`, `address_area_id`, `birthplace_area_id`, `postal_code`, `photo_name`, `photo_content`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `students_bak`;

-- DROP TABLE IF EXISTS `students_bak`;

--
-- 2. chnage table staff
--

RENAME TABLE `staff` TO `staff_bak` ;

CREATE TABLE `staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identification_no` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `preferred_name` varchar(100) DEFAULT NULL,
  `gender` char(1) NOT NULL COMMENT 'M for Male, F for Female',
  `date_of_birth` date NOT NULL,
  `date_of_death` date DEFAULT NULL,
  `address` text,
  `address_area_id` int(11) DEFAULT '0',
  `birthplace_area_id` int(11) DEFAULT '0',
  `postal_code` varchar(20) DEFAULT NULL,
  `photo_name` varchar(250) DEFAULT '',
  `photo_content` longblob,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `birthplace_area_id` (`birthplace_area_id`),
  KEY `address_area_id` (`address_area_id`),
  KEY `first_name` (`first_name`),
  KEY `last_name` (`last_name`),
  KEY `middle_name` (`middle_name`),
  KEY `identification_no` (`identification_no`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

INSERT INTO `staff` (`id`, `identification_no`, `first_name`, `middle_name`, `last_name`, `preferred_name`, `gender`, `date_of_birth`, `date_of_death`, `address`, `address_area_id`, `birthplace_area_id`, `postal_code`, `photo_name`, `photo_content`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT `id`, `identification_no`, `first_name`, `middle_name`, `last_name`, `preferred_name`, `gender`, `date_of_birth`, `date_of_death`, `address`, `address_area_id`, `birthplace_area_id`, `postal_code`, `photo_name`, `photo_content`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM staff_bak;

-- DROP TABLE IF EXISTS `staff_bak`;