-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2378', NOW());

CREATE TABLE `examinations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `education_grade_id` (`education_grade_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the examination for a specific grade';

CREATE TABLE `examination_items` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` decimal(6,2) DEFAULT '0.00',
  `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
  `examination_grading_type_id` int(11) NOT NULL COMMENT 'links to examination_grading_types.id',
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `examination_id` (`examination_id`),
  KEY `education_subject_id` (`education_subject_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `examination_grading_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pass_mark` decimal(6,2) NOT NULL,
  `max` decimal(6,2) NOT NULL,
  `result_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of grading types that can be used for an examination subject';

CREATE TABLE `examination_grading_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `min` decimal(6,2) DEFAULT NULL,
  `max` decimal(6,2) DEFAULT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `examination_grading_type_id` int(11) NOT NULL COMMENT 'links to examination_grading_types.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `examination_grading_type_id` (`examination_grading_type_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all options linked to a specific grading type';

CREATE TABLE `examination_centres` (
  `id` INT(11) NOT NULL,
  `examination_id` INT(11) NOT NULL COMMENT 'links to examinations.id',
  `academic_period_id` INT(11) NOT NULL COMMENT 'links to academic_periods.id',
  `institution_id` INT(11) NULL COMMENT 'links to institutions.id',
  `name` VARCHAR(150) NULL,
  `area_id` INT(11) NULL,
  `code` VARCHAR(50) NULL,
  `address` TEXT NULL,
  `postal_code` VARCHAR(20) NULL,
  `contact_person` VARCHAR(100) NULL,
  `telephone` VARCHAR(20) NULL,
  `fax` VARCHAR(20) NULL,
  `email` VARCHAR(100) NULL,
  `website` VARCHAR(100) NULL,
  `capacity` INT(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `examination_id` (`examination_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `institution_id` (`institution_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the examination centres for a particular examination';

CREATE TABLE `examination_centre_subjects` (
  `id` INT(11) NOT NULL,
  `examination_centre_id` INT(11) NOT NULL COMMENT 'links to examination_centres.id',
  `academic_period_id` INT(11) NOT NULL COMMENT 'links to academic_periods.id',
  `education_subject_id` INT(11) NOT NULL COMMENT 'links to education_subjects.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `education_subject_id` (`education_subject_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the examination centres for a particular examination subject';

CREATE TABLE `examination_centre_special_needs` (
  `id` INT(11) NOT NULL,
  `examination_centre_id` INT(11) NOT NULL COMMENT 'links to examination_centres.id',
  `academic_period_id` INT(11) NOT NULL COMMENT 'links to academic_periods.id',
  `special_need_type_id` INT(11) NOT NULL COMMENT 'links to special_need_types.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the special needs for a particular examination centre';

CREATE TABLE `examination_item_results` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marks` decimal(6,2) DEFAULT NULL,
  `examination_grading_option_id` int(11) DEFAULT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `examination_id` int(11) NOT NULL,
  `education_subject_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `examination_centre_id` int(11) NULL,
  `academic_period_id` int(11) NOT NULL,
  `examination_period_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`student_id`,`examination_id`,`education_subject_id`,`institution_id`,`academic_period_id`,`examination_period_id`),
  KEY `examination_grading_option_id` (`examination_grading_option_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the examination results for an individual student in an institution and examination centre'
/*!50100 PARTITION BY HASH (`academic_period_id`)
PARTITIONS 8 */
