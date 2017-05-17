-- POCOR-3271
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3271', NOW());

-- `institution_genders`
RENAME TABLE `institution_genders` TO `z_3271_institution_genders`;

DROP TABLE IF EXISTS `institution_genders`;
CREATE TABLE IF NOT EXISTS `institution_genders` (
  `id` int(11) NOT NULL,
  `name` varchar(10) NOT NULL,
  `code` varchar(10) NOT NULL,
  `order` int(3) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains the types of institution gender used by institution';

ALTER TABLE `institution_genders`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `institution_genders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;

INSERT INTO `institution_genders` (`id`, `name`, `code`, `order`, `created_user_id`, `created`) VALUES
(1, 'Mixed', 'X', 1, 1, '2017-04-13 00:00:00'),
(2, 'Male', 'M', 2, 1, '2017-04-13 00:00:00'),
(3, 'Female', 'F', 3, 1, '2017-04-13 00:00:00');

UPDATE z_3271_institution_genders SET name = 'Female' WHERE name = 'Girls';
UPDATE z_3271_institution_genders SET name = 'Male' WHERE name = 'Boys';

UPDATE z_3271_institution_genders
JOIN institution_genders ON institution_genders.name = z_3271_institution_genders.name
SET `national_code` = institution_genders.id;

UPDATE institutions
JOIN z_3271_institution_genders
    ON z_3271_institution_genders.id = institutions.institution_gender_id
SET institutions.institution_gender_id = z_3271_institution_genders.`national_code`;

UPDATE institutions
SET institution_gender_id = 1
WHERE NOT EXISTS (
    SELECT 1 FROM institution_genders WHERE id = institution_gender_id
);

-- POCOR-3690
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3690', NOW());

-- examination_centres_examinations
DROP TABLE IF EXISTS `examination_centres_examinations`;
CREATE TABLE IF NOT EXISTS `examination_centres_examinations` (
 `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
 `total_registered` int(11) NOT NULL DEFAULT '0',
 `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
 `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
 `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`examination_centre_id`, `examination_id`),
 KEY `examination_centre_id` (`examination_centre_id`),
 KEY `examination_id` (`examination_id`),
 KEY `academic_period_id` (`academic_period_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the examination centres for a particular examination';

INSERT INTO `examination_centres_examinations` (`id`, `total_registered`, `examination_centre_id`, `examination_id`, `academic_period_id`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`id`, ',', `examination_id`), '256'), `total_registered`, `id`, `examination_id`, `academic_period_id`, `created_user_id`, `created`
FROM `examination_centres`;

-- examination_centres
RENAME TABLE `examination_centres` TO `z_3690_examination_centres`;

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
 `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
 `area_id` int(11) NOT NULL COMMENT 'links to areas.id',
 `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`id`),
 KEY `institution_id` (`institution_id`),
 KEY `area_id` (`area_id`),
 KEY `academic_period_id` (`academic_period_id`),
 KEY `modified_user_id` (`modified_user_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the examination centres for an academic period';

INSERT INTO `examination_centres` (`id`, `name`, `code`, `address`, `postal_code`, `contact_person`, `telephone`, `fax`, `email`, `website`, `institution_id`, `area_id`, `academic_period_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`, `code`, `address`, `postal_code`, `contact_person`, `telephone`, `fax`, `email`, `website`, `institution_id`, `area_id`, `academic_period_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3690_examination_centres`;

-- examination_centre_special_needs
RENAME TABLE `examination_centre_special_needs` TO `z_3690_examination_centre_special_needs`;

DROP TABLE IF EXISTS `examination_centre_special_needs`;
CREATE TABLE IF NOT EXISTS `examination_centre_special_needs` (
 `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
 `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
 `special_need_type_id` int(11) NOT NULL COMMENT 'links to special_need_types.id',
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`examination_centre_id`,`special_need_type_id`),
 KEY `examination_centre_id` (`examination_centre_id`),
 KEY `special_need_type_id` (`special_need_type_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the special needs for a particular examination centre';

INSERT INTO `examination_centre_special_needs` (`id`, `examination_centre_id`, `special_need_type_id`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`examination_centre_id`, ',', `special_need_type_id`), '256'), `examination_centre_id`, `special_need_type_id`, `created_user_id`, `created`
FROM `z_3690_examination_centre_special_needs`;

-- examination_centres_examinations_institutions
RENAME TABLE `examination_centres_institutions` TO `z_3690_examination_centres_institutions`;

DROP TABLE IF EXISTS `examination_centres_examinations_institutions`;
CREATE TABLE IF NOT EXISTS `examination_centres_examinations_institutions` (
 `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
 `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
 `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
 `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`examination_centre_id`, `examination_id`, `institution_id`),
 KEY `examination_centre_id` (`examination_centre_id`),
 KEY `examination_id` (`examination_id`),
 KEY `institution_id` (`institution_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of institutions linked to a particular examination centre';

INSERT INTO `examination_centres_examinations_institutions` (`id`, `examination_centre_id`, `examination_id`, `institution_id`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`examination_centre_id`, ',', `examination_id`, ',', `institution_id`), '256'), `examination_centre_id`, `examination_id`, `institution_id`, 1, NOW()
FROM `z_3690_examination_centres_institutions`;

-- examination_centres_examinations_invigilators
RENAME TABLE `examination_centres_invigilators` TO `z_3690_examination_centres_invigilators`;

DROP TABLE IF EXISTS `examination_centres_examinations_invigilators`;
CREATE TABLE IF NOT EXISTS `examination_centres_examinations_invigilators` (
 `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
 `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
 `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
 `invigilator_id` int(11) NOT NULL COMMENT 'links to security_users.id',
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`examination_centre_id`, `examination_id`, `invigilator_id`),
 KEY `examination_centre_id` (`examination_centre_id`),
 KEY `examination_id` (`examination_id`),
 KEY `invigilator_id` (`invigilator_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the invigilators for a particular examination centre';

INSERT INTO `examination_centres_examinations_invigilators` (`id`, `examination_centre_id`, `examination_id`, `invigilator_id`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`examination_centre_id`, ',', `examination_id`, ',', `invigilator_id`), '256'), `examination_centre_id`, `examination_id`, `invigilator_id`, 1, NOW()
FROM `z_3690_examination_centres_invigilators`;

-- examination_centres_examinations_subjects
RENAME TABLE `examination_centre_subjects` TO `z_3690_examination_centre_subjects`;

DROP TABLE IF EXISTS `examination_centres_examinations_subjects`;
CREATE TABLE IF NOT EXISTS `examination_centres_examinations_subjects` (
 `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
 `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
 `examination_item_id` int(11) NOT NULL COMMENT 'links to `examination_items.id',
 `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
 `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`examination_centre_id`, `examination_item_id`),
 KEY `examination_centre_id` (`examination_centre_id`),
 KEY `examination_item_id` (`examination_item_id`),
 KEY `education_subject_id` (`education_subject_id`),
 KEY `examination_id` (`examination_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the examination centres for a particular examination subject';

INSERT INTO `examination_centres_examinations_subjects` (`id`, `examination_centre_id`, `examination_item_id`, `education_subject_id`, `examination_id`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`examination_centre_id`, ',', `examination_item_id`), '256'), `examination_centre_id`, `examination_item_id`, `education_subject_id`, `examination_id`, `created_user_id`, `created`
FROM `z_3690_examination_centre_subjects`;

-- examination_centres_examinations_subjects_students
DROP TABLE IF EXISTS `examination_centres_examinations_subjects_students`;
CREATE TABLE IF NOT EXISTS `examination_centres_examinations_subjects_students` (
 `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
 `total_mark` decimal(6,2) DEFAULT NULL,
 `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
 `examination_item_id` int(11) NOT NULL COMMENT 'links to `examination_items.id',
 `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
 `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
 `education_subject_id` int(11) NOT NULL COMMENT 'links to `education_subjects.id',
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`examination_centre_id`, `examination_item_id`, `student_id`),
 KEY `examination_centre_id` (`examination_centre_id`),
 KEY `examination_item_id` (`examination_item_id`),
 KEY `student_id` (`student_id`),
 KEY `examination_id` (`examination_id`),
 KEY `education_subject_id` (`education_subject_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the students registered to an examination center for a particular examination subject';

INSERT INTO `examination_centres_examinations_subjects_students` (`id`, `total_mark`, `examination_centre_id`, `examination_item_id`, `student_id`, `examination_id`, `education_subject_id`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`examination_centre_id`, ',', `examination_item_id`, ',', `student_id`), '256'), `total_mark`, `examination_centre_id`, `examination_item_id`, `student_id`, `examination_id`, `education_subject_id`, `created_user_id`, `created`
FROM `examination_centre_students`;

-- examination_centres_examinations_students
RENAME TABLE `examination_centre_students` TO `z_3690_examination_centre_students`;

DROP TABLE IF EXISTS `examination_centres_examinations_students`;
CREATE TABLE IF NOT EXISTS `examination_centres_examinations_students` (
 `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
 `registration_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
 `examination_id` int(11) NOT NULL COMMENT 'links to examination.id',
 `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
 `institution_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to institutions.id',
 `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`examination_centre_id`, `examination_id`, `student_id`),
 KEY `examination_centre_id` (`examination_centre_id`),
 KEY `examination_id` (`examination_id`),
 KEY `student_id` (`student_id`),
 KEY `institution_id` (`institution_id`),
 KEY `academic_period_id` (`academic_period_id`),
 KEY `modified_user_id` (`modified_user_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the students registered to an examination center for a particular examination';

INSERT INTO `examination_centres_examinations_students` (`id`, `registration_number`, `examination_centre_id`, `examination_id`, `student_id`, `institution_id`, `academic_period_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`examination_centre_id`, ',', `examination_id`, ',', `student_id`), '256'), `registration_number`, `examination_centre_id`, `examination_id`, `student_id`, `institution_id`, `academic_period_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3690_examination_centre_students`
GROUP BY `examination_centre_id`, `examination_id`, `student_id`;

-- examination_centre_rooms_examinations
DROP TABLE IF EXISTS `examination_centre_rooms_examinations`;
CREATE TABLE IF NOT EXISTS `examination_centre_rooms_examinations` (
 `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
 `examination_centre_room_id` int(11) NOT NULL COMMENT 'links to examination_centre_rooms.id',
 `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
 `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`examination_centre_room_id`, `examination_id`),
 KEY `examination_centre_room_id` (`examination_centre_room_id`),
 KEY `examination_id` (`examination_id`),
 KEY `examination_centre_id` (`examination_centre_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the examination centres for a particular examination';

INSERT INTO `examination_centre_rooms_examinations` (`id`, `examination_centre_room_id`, `examination_id`, `examination_centre_id`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`id`, ',', `examination_id`), '256'), `id`, `examination_id`, `examination_centre_id`, `created_user_id`, `created`
FROM `examination_centre_rooms`;

-- examination_centre_rooms
RENAME TABLE `examination_centre_rooms` TO `z_3690_examination_centre_rooms`;

DROP TABLE IF EXISTS `examination_centre_rooms`;
CREATE TABLE IF NOT EXISTS `examination_centre_rooms` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
 `size` int(3) DEFAULT '0',
 `number_of_seats` int(3) DEFAULT '0',
 `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`id`),
 KEY `examination_centre_id` (`examination_centre_id`),
 KEY `modified_user_id` (`modified_user_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the rooms for a particular examination centre';

INSERT INTO `examination_centre_rooms` (`id`, `name`, `size`, `number_of_seats`, `examination_centre_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`, `size`, `number_of_seats`, `examination_centre_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3690_examination_centre_rooms`;

-- examination_centre_rooms_examinations_invigilators
RENAME TABLE `examination_centre_rooms_invigilators` TO `z_3690_examination_centre_rooms_invigilators`;

DROP TABLE IF EXISTS `examination_centre_rooms_examinations_invigilators`;
CREATE TABLE IF NOT EXISTS `examination_centre_rooms_examinations_invigilators` (
 `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
 `examination_centre_room_id` int(11) NOT NULL COMMENT 'links to examination_centre_rooms.id',
 `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
 `invigilator_id` int(11) NOT NULL COMMENT 'links to security_users.id',
 `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`examination_centre_room_id`, `examination_id`, `invigilator_id`),
 KEY `examination_centre_room_id` (`examination_centre_room_id`),
 KEY `examination_id` (`examination_id`),
 KEY `invigilator_id` (`invigilator_id`),
 KEY `examination_centre_id` (`examination_centre_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the invigilators assigned to a room for a particular examination centre';

INSERT INTO `examination_centre_rooms_examinations_invigilators` (`id`, `examination_centre_room_id`, `examination_id`, `invigilator_id`, `examination_centre_id`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`examination_centre_room_id`, ',', `examination_id`, ',', `invigilator_id`), '256'), `examination_centre_room_id`, `examination_id`,  `invigilator_id`, `examination_centre_id`, 1, NOW()
FROM `z_3690_examination_centre_rooms_invigilators`;

-- examination_centre_rooms_examinations_students
RENAME TABLE `examination_centre_room_students` TO `z_3690_examination_centre_room_students`;

DROP TABLE IF EXISTS `examination_centre_rooms_examinations_students`;
CREATE TABLE IF NOT EXISTS `examination_centre_rooms_examinations_students` (
 `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
 `examination_centre_room_id` int(11) NOT NULL COMMENT 'links to examination_centre_rooms.id',
 `examination_id` int(11) NOT NULL COMMENT 'links to examination.id',
 `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
 `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`examination_centre_room_id`, `examination_id`, `student_id`),
 KEY `examination_centre_room_id` (`examination_centre_room_id`),
 KEY `examination_id` (`examination_id`),
 KEY `student_id` (`student_id`),
 KEY `examination_centre_id` (`examination_centre_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the students allocated to a room for a particular examination center';

INSERT INTO `examination_centre_rooms_examinations_students` (`id`, `examination_centre_room_id`, `examination_id`, `student_id`, `examination_centre_id`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`examination_centre_room_id`, ',', `examination_id`, ',', `student_id`), '256'), `examination_centre_room_id`, `examination_id`, `student_id`, `examination_centre_id`, `created_user_id`, `created`
FROM `z_3690_examination_centre_room_students`;

-- examination_item_results
RENAME TABLE `examination_item_results` TO `z_3690_examination_item_results`;

DROP TABLE IF EXISTS `examination_item_results`;
CREATE TABLE IF NOT EXISTS `examination_item_results` (
 `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
 `marks` decimal(6,2) DEFAULT NULL,
 `examination_item_id` int(11) NOT NULL COMMENT 'links to `examination_items.id',
 `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
 `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
 `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
 `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
 `education_subject_id` int(11) NOT NULL COMMENT 'links to `education_subjects.id',
 `examination_grading_option_id` int(11) DEFAULT NULL COMMENT 'links to examination_grading_options.id',
 `institution_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to institutions.id',
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`examination_item_id`,`student_id`),
 KEY `examination_item_id` (`examination_item_id`),
 KEY `student_id` (`student_id`),
 KEY `academic_period_id` (`academic_period_id`),
 KEY `examination_id` (`examination_id`),
 KEY `examination_centre_id` (`examination_centre_id`),
 KEY `education_subject_id` (`education_subject_id`),
 KEY `examination_grading_option_id` (`examination_grading_option_id`),
 KEY `institution_id` (`institution_id`),
 KEY `modified_user_id` (`modified_user_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the examination results for an individual student in a particular examination';

INSERT INTO `examination_item_results` (`id`, `marks`, `examination_item_id`, `student_id`, `academic_period_id`, `examination_id`, `examination_centre_id`, `education_subject_id`, `examination_grading_option_id`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`examination_item_id`, ',', `student_id`), '256'), `marks`, `examination_item_id`, `student_id`, `academic_period_id`, `examination_id`, `examination_centre_id`, `education_subject_id`, `examination_grading_option_id`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3690_examination_item_results`;

-- import_mapping
CREATE TABLE `z_3690_import_mapping` LIKE `import_mapping`;
INSERT INTO `z_3690_import_mapping` SELECT * FROM `import_mapping`
WHERE `model` = 'Examination.ExaminationCentreRooms';

DELETE FROM `import_mapping` WHERE `model` = 'Examination.ExaminationCentreRooms' AND `column_name` = 'examination_id';
UPDATE `import_mapping` SET `order` = `order` - 1
WHERE `model` = 'Examination.ExaminationCentreRooms';

-- security_functions
UPDATE `security_functions`
SET `order` = `order` + 4
WHERE `order` >= 5052 AND `order` <= 5067;

UPDATE `security_functions`
SET `_edit` = 'ExamCentres.edit|ExamCentreExams.add'
WHERE `controller` = 'Examinations' AND `name` = 'Exam Centres';

UPDATE `security_functions`
SET `_add` = NULL, `_delete` = NULL, `_edit` = 'ExamCentreStudents.edit', `order` = 5053
WHERE `controller` = 'Examinations' AND `name` = 'Exam Centre Students';

UPDATE `security_functions`
SET `order` = 5050
WHERE `controller` = 'Examinations' AND `name` = 'Exam Centre Rooms';

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(5068, 'Exam Centre Exams', 'Examinations', 'Administration', 'Examinations', 5000, 'ExamCentreExams.index', NULL, NULL, 'ExamCentreExams.remove', NULL, 5051, 1, NULL, NULL, NULL, 1, NOW()),
(5069, 'Exam Centre Subjects', 'Examinations', 'Administration', 'Examinations', 5000, 'ExamCentreSubjects.index|ExamCentreSubjects.view', NULL, NULL, NULL, NULL, 5052, 1, NULL, NULL, NULL, 1, NOW()),
(5070, 'Exam Centre Invigilators', 'Examinations', 'Administration', 'Examinations', 5000, 'ExamCentreInvigilators.index|ExamCentreInvigilators.view', 'ExamCentreInvigilators.edit', 'ExamCentreInvigilators.add', 'ExamCentreInvigilators.remove', NULL, 5054, 1, NULL, NULL, NULL, 1, NOW()),
(5071, 'Exam Centre Linked Institutions', 'Examinations', 'Administration', 'Examinations', 5000, 'ExamCentreLinkedInstitutions.index|ExamCentreLinkedInstitutions.view', NULL, 'ExamCentreLinkedInstitutions.add', 'ExamCentreLinkedInstitutions.remove', NULL, 5055, 1, NULL, NULL, NULL, 1, NOW());


-- POCOR-2879
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-2879', NOW());

ALTER TABLE `institution_subjects`
ADD COLUMN `education_grade_id` INT NULL AFTER `institution_id`;

UPDATE institution_subjects
INNER JOIN institution_class_subjects ON institution_subjects.id = institution_class_subjects.institution_subject_id
INNER JOIN institution_class_grades ON institution_class_grades.institution_class_id = institution_class_subjects.institution_class_id
INNER JOIN education_grades_subjects ON education_grades_subjects.education_grade_id = institution_class_grades.education_grade_id AND institution_subjects.education_subject_id = education_grades_subjects.education_subject_id
SET institution_subjects.education_grade_id = education_grades_subjects.education_grade_id;

UPDATE `institution_subjects` SET `education_grade_id` = 0 WHERE `education_grade_id` IS NULL;

ALTER TABLE `institution_subjects`
CHANGE COLUMN `education_grade_id` `education_grade_id` INT(11) NOT NULL ;


-- POCOR-3516
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3516', NOW());

-- custom_field_types
CREATE TABLE `z_3516_custom_field_types`  LIKE `custom_field_types`;
INSERT INTO `z_3516_custom_field_types` SELECT * FROM `custom_field_types`;

UPDATE `custom_field_types` SET `id` = `id`+1 WHERE `id` >= 3 order by id desc;

INSERT INTO `custom_field_types` (`id`, `code`, `name`, `value`, `description`, `format`, `is_mandatory`, `is_unique`, `visible`)
VALUES (3, 'DECIMAL', 'Decimal', 'decimal_value', '', 'OpenEMIS', '1', '0', '1');

-- custom_field_values
RENAME TABLE `custom_field_values` TO `z_3516_custom_field_values`;

DROP TABLE IF EXISTS `custom_field_values`;
CREATE TABLE IF NOT EXISTS `custom_field_values` (
    `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
    `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `number_value` int(11) DEFAULT NULL,
    `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `textarea_value` text COLLATE utf8mb4_unicode_ci,
    `date_value` date DEFAULT NULL,
    `time_value` time DEFAULT NULL,
    `file` longblob,
    `custom_field_id` int(11) NOT NULL COMMENT 'links to custom_fields.id',
    `custom_record_id` int(11) NOT NULL COMMENT 'links to custom_records.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `number_value` (`number_value`),
    KEY `custom_field_id` (`custom_field_id`),
    KEY `custom_record_id` (`custom_record_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `custom_field_id`, `custom_record_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `custom_field_id`, `custom_record_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_custom_field_values`;

-- institution_custom_field_values
RENAME TABLE `institution_custom_field_values` TO `z_3516_institution_custom_field_values`;

DROP TABLE IF EXISTS `institution_custom_field_values`;
CREATE TABLE IF NOT EXISTS `institution_custom_field_values` (
    `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
    `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `number_value` int(11) DEFAULT NULL,
    `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `textarea_value` text COLLATE utf8mb4_unicode_ci,
    `date_value` date DEFAULT NULL,
    `time_value` time DEFAULT NULL,
    `file` longblob,
    `institution_custom_field_id` int(11) NOT NULL COMMENT 'links to institution_custom_fields.id',
    `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `number_value` (`number_value`),
    KEY `institution_custom_field_id` (`institution_custom_field_id`),
    KEY `institution_id` (`institution_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `institution_custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `institution_custom_field_id`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `institution_custom_field_id`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_institution_custom_field_values`;

-- infrastructure_custom_field_values
RENAME TABLE `infrastructure_custom_field_values` TO `z_3516_infrastructure_custom_field_values`;

DROP TABLE IF EXISTS `infrastructure_custom_field_values`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_field_values` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `infrastructure_custom_field_id` int(11) NOT NULL COMMENT 'links to infrastructure_custom_fields.id',
  `institution_infrastructure_id` int(11) NOT NULL COMMENT 'links to institution_infrastructures.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number_value` (`number_value`),
  KEY `infrastructure_custom_field_id` (`infrastructure_custom_field_id`),
  KEY `institution_infrastructure_id` (`institution_infrastructure_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `infrastructure_custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_infrastructure_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_infrastructure_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_infrastructure_custom_field_values`;

-- room_custom_field_values
RENAME TABLE `room_custom_field_values` TO `z_3516_room_custom_field_values`;

DROP TABLE IF EXISTS `room_custom_field_values`;
CREATE TABLE IF NOT EXISTS `room_custom_field_values` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `institution_room_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number_value` (`number_value`),
  KEY `infrastructure_custom_field_id` (`infrastructure_custom_field_id`),
  KEY `institution_room_id` (`institution_room_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `room_custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_room_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_room_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_room_custom_field_values`;

-- staff_custom_field_values
RENAME TABLE `staff_custom_field_values` TO `z_3516_staff_custom_field_values`;

DROP TABLE IF EXISTS `staff_custom_field_values`;
CREATE TABLE IF NOT EXISTS `staff_custom_field_values` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `staff_custom_field_id` int(11) NOT NULL COMMENT 'links to staff_custom_fields.id',
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number_value` (`number_value`),
  KEY `staff_custom_field_id` (`staff_custom_field_id`),
  KEY `staff_id` (`staff_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `staff_custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `staff_custom_field_id`, `staff_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `staff_custom_field_id`, `staff_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_staff_custom_field_values`;

-- student_custom_field_values
RENAME TABLE `student_custom_field_values` TO `z_3516_student_custom_field_values`;

DROP TABLE IF EXISTS `student_custom_field_values`;
CREATE TABLE IF NOT EXISTS `student_custom_field_values` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `student_custom_field_id` int(11) NOT NULL COMMENT 'links to student_custom_fields.id',
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number_value` (`number_value`),
  KEY `student_custom_field_id` (`student_custom_field_id`),
  KEY `student_id` (`student_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `student_custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `student_custom_field_id`, `student_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `student_custom_field_id`, `student_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_student_custom_field_values`;

-- institution_survey_answers
RENAME TABLE `institution_survey_answers` TO `z_3516_institution_survey_answers`;

DROP TABLE IF EXISTS `institution_survey_answers`;
CREATE TABLE IF NOT EXISTS `institution_survey_answers` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id',
  `institution_survey_id` int(11) NOT NULL COMMENT 'links to institution_surveys.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number_value` (`number_value`),
  KEY `survey_question_id` (`survey_question_id`),
  KEY `institution_survey_id` (`institution_survey_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the answers to each question in a form';

INSERT INTO `institution_survey_answers` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `survey_question_id`, `institution_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `survey_question_id`, `institution_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_institution_survey_answers`;

-- institution_student_survey_answers
RENAME TABLE `institution_student_survey_answers` TO `z_3516_institution_student_survey_answers`;

DROP TABLE IF EXISTS `institution_student_survey_answers`;
CREATE TABLE IF NOT EXISTS `institution_student_survey_answers` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id',
  `institution_student_survey_id` int(11) NOT NULL COMMENT 'links to institution_student_surveys.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number_value` (`number_value`),
  KEY `survey_question_id` (`survey_question_id`),
  KEY `institution_student_survey_id` (`institution_student_survey_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the student list answers of a survey';

INSERT INTO `institution_student_survey_answers` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `survey_question_id`, `institution_student_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `survey_question_id`, `institution_student_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_institution_student_survey_answers`;

-- institution_repeater_survey_answers
RENAME TABLE `institution_repeater_survey_answers` TO `z_3516_institution_repeater_survey_answers`;

DROP TABLE IF EXISTS `institution_repeater_survey_answers`;
CREATE TABLE IF NOT EXISTS `institution_repeater_survey_answers` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id',
  `institution_repeater_survey_id` int(11) NOT NULL COMMENT 'links to institution_repeater_surveys.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number_value` (`number_value`),
  KEY `survey_question_id` (`survey_question_id`),
  KEY `institution_repeater_survey_id` (`institution_repeater_survey_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains repeater type answers of a survey';

INSERT INTO `institution_repeater_survey_answers` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `survey_question_id`, `institution_repeater_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `survey_question_id`, `institution_repeater_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_institution_repeater_survey_answers`;


-- 3.9.12
UPDATE config_items SET value = '3.9.12' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
