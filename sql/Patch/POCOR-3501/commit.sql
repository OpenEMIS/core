-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3501', NOW());

-- examinations
RENAME TABLE `examinations` TO `z_3501_examinations`;

DROP TABLE IF EXISTS `examinations`;
CREATE TABLE IF NOT EXISTS `examinations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `registration_start_date` date NOT NULL,
  `registration_end_date` date NOT NULL,
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

INSERT INTO `examinations` (`id`, `code`, `name`, `description`, `registration_start_date`, `registration_end_date`, `academic_period_id`, `education_grade_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `code`, `name`, `description`, `registration_start_date`, `registration_end_date`, `academic_period_id`, `education_grade_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3501_examinations`;

-- examination_centres
RENAME TABLE `examination_centres` TO `z_3501_examination_centres`;

DROP TABLE IF EXISTS `examination_centres`;
CREATE TABLE IF NOT EXISTS `examination_centres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_registered` int(11) NOT NULL DEFAULT '0',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `area_id` int(11) NOT NULL COMMENT 'links to areas.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_id` (`institution_id`),
  KEY `area_id` (`area_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `examination_id` (`examination_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the examination centres for a particular examination';

INSERT INTO `examination_centres` (`id`, `name`, `code`, `address`, `postal_code`, `contact_person`, `telephone`, `fax`, `email`, `website`, `total_registered`, `institution_id`, `area_id`, `academic_period_id`, `examination_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`, `code`, `address`, `postal_code`, `contact_person`, `telephone`, `fax`, `email`, `website`, `total_registered`, `institution_id`, `area_id`, `academic_period_id`, `examination_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3501_examination_centres`;

-- examination_centre_special_needs
RENAME TABLE `examination_centre_special_needs` TO `z_3501_examination_centre_special_needs`;

DROP TABLE IF EXISTS `examination_centre_special_needs`;
CREATE TABLE IF NOT EXISTS `examination_centre_special_needs` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
  `special_need_type_id` int(11) NOT NULL COMMENT 'links to special_need_types.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`examination_centre_id`,`special_need_type_id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `special_need_type_id` (`special_need_type_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `examination_id` (`examination_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the special needs for a particular examination centre';

INSERT INTO `examination_centre_special_needs` (`id`, `examination_centre_id`, `special_need_type_id`, `academic_period_id`, `examination_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `examination_centre_id`, `special_need_type_id`, `academic_period_id`, `examination_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3501_examination_centre_special_needs`;

-- examination_centre_students
RENAME TABLE `examination_centre_students` TO `z_3501_examination_centre_students`;

DROP TABLE IF EXISTS `examination_centre_students`;
CREATE TABLE IF NOT EXISTS `examination_centre_students` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registration_number` varchar(20) DEFAULT NULL,
  `total_mark` decimal(6,2) DEFAULT NULL,
  `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to `education_subjects.id',
  `institution_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to institutions.id',
  `education_grade_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to education_grades.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_period.id',
  `examination_id` int(11) NOT NULL COMMENT 'links to examination.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`examination_centre_id`,`student_id`,`education_subject_id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `student_id` (`student_id`),
  KEY `education_subject_id` (`education_subject_id`),
  KEY `institution_id` (`institution_id`),
  KEY `education_grade_id` (`education_grade_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `examination_id` (`examination_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the students registered to an examination center for a particular examination';

INSERT INTO `examination_centre_students` (`id`, `registration_number`, `total_mark`, `examination_centre_id`, `student_id`, `education_subject_id`, `institution_id`, `education_grade_id`, `academic_period_id`, `examination_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, NULL, NULL, `examination_centre_id`, `student_id`, `education_subject_id`, `institution_id`, `education_grade_id`, `academic_period_id`, `examination_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3501_examination_centre_students`;

-- examination_centre_subjects
RENAME TABLE `examination_centre_subjects` TO `z_3501_examination_centre_subjects`;

DROP TABLE IF EXISTS `examination_centre_subjects`;
CREATE TABLE IF NOT EXISTS `examination_centre_subjects` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`examination_centre_id`,`education_subject_id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `education_subject_id` (`education_subject_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `examination_id` (`examination_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the examination centres for a particular examination subject';

INSERT INTO `examination_centre_subjects` (`id`, `examination_centre_id`, `education_subject_id`, `academic_period_id`, `examination_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `examination_centre_id`, `education_subject_id`, `academic_period_id`, `examination_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3501_examination_centre_subjects`;

-- examination_centre_invigilators
DROP TABLE IF EXISTS `examination_centre_invigilators`;
CREATE TABLE IF NOT EXISTS `examination_centre_invigilators` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
  `invigilator_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`examination_centre_id`,`invigilator_id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `invigilator_id` (`invigilator_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `examination_id` (`examination_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the invigilators for a particular examination centre';

-- examination_centre_rooms
DROP TABLE IF EXISTS `examination_centre_rooms`;
CREATE TABLE IF NOT EXISTS `examination_centre_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` float DEFAULT NULL,
  `number_of_seats` int(3) DEFAULT NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
  `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `examination_id` (`examination_id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the rooms for a particular examination centre';

-- examination_centre_room_students
DROP TABLE IF EXISTS `examination_centre_room_students`;
CREATE TABLE IF NOT EXISTS `examination_centre_room_students` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `examination_centre_room_id` int(11) NOT NULL COMMENT 'links to examination_centre_rooms.id',
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to institutions.id',
  `education_grade_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to education_grades.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_period.id',
  `examination_id` int(11) NOT NULL COMMENT 'links to examination.id',
  `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`examination_centre_room_id`,`student_id`),
  KEY `examination_centre_room_id` (`examination_centre_room_id`),
  KEY `student_id` (`student_id`),
  KEY `institution_id` (`institution_id`),
  KEY `education_grade_id` (`education_grade_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `examination_id` (`examination_id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the students allocated to a room for a particular examination center';

-- examination_centre_room_invigilators
DROP TABLE IF EXISTS `examination_centre_room_invigilators`;
CREATE TABLE IF NOT EXISTS `examination_centre_room_invigilators` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `examination_centre_room_id` int(11) NOT NULL COMMENT 'links to examination_centre_rooms.id',
  `invigilator_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
  `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`examination_centre_room_id`,`invigilator_id`),
  KEY `examination_centre_room_id` (`examination_centre_room_id`),
  KEY `invigilator_id` (`invigilator_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `examination_id` (`examination_id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the invigilators assigned to a room for a particular examination centre';

-- examination_centres_institutions
DROP TABLE IF EXISTS `examination_centres_institutions`;
CREATE TABLE IF NOT EXISTS `examination_centres_institutions` (
  `id` char(64) NOT NULL,
  `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  PRIMARY KEY (`examination_centre_id`, `institution_id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `institution_id` (`institution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of institutions linked to a particular examination centre';

-- examination_item_results
DROP TABLE IF EXISTS `examination_item_results`;
CREATE TABLE IF NOT EXISTS `examination_item_results` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marks` decimal(6,2) DEFAULT NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to `education_subjects.id',
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
  `examination_grading_option_id` int(11) DEFAULT NULL COMMENT 'links to examination_grading_options.id',
  `institution_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to institutions.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`academic_period_id`,`examination_id`,`education_subject_id`,`student_id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `examination_grading_option_id` (`examination_grading_option_id`),
  KEY `institution_id` (`institution_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the examination results for an individual student in a particular examination'
/*!50100 PARTITION BY HASH (`academic_period_id`)
PARTITIONS 8 */;
