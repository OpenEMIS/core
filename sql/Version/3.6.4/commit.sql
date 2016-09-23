-- POCOR-2378
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2378', NOW());

CREATE TABLE `examinations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
  `registration_start_date` date NOT NULL,
  `registration_end_date` date NOT NULL,
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
  `examination_date` date NOT NULL,
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
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `examination_id` INT(11) NOT NULL COMMENT 'links to examinations.id',
  `academic_period_id` INT(11) NOT NULL COMMENT 'links to academic_periods.id',
  `institution_id` INT(11) NOT NULL COMMENT 'links to institutions.id',
  `area_id` INT(11) NOT NULL COMMENT 'links to areas.id',
  `name` VARCHAR(150) NULL,
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
  KEY `area_id` (`area_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the examination centres for a particular examination';

CREATE TABLE `examination_centre_subjects` (
  `id` char(36) NOT NULL,
  `examination_id` INT(11) NOT NULL COMMENT 'links to examinations.id',
  `examination_centre_id` INT(11) NOT NULL COMMENT 'links to examination_centres.id',
  `academic_period_id` INT(11) NOT NULL COMMENT 'links to academic_periods.id',
  `education_subject_id` INT(11) NOT NULL COMMENT 'links to education_subjects.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`examination_centre_id`, `education_subject_id`),
  KEY `examination_id` (`examination_id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `education_subject_id` (`education_subject_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the examination centres for a particular examination subject';

CREATE TABLE `examination_centre_special_needs` (
  `id` char(36) NOT NULL,
  `examination_id` INT(11) NOT NULL COMMENT 'links to examinations.id',
  `examination_centre_id` INT(11) NOT NULL COMMENT 'links to examination_centres.id',
  `academic_period_id` INT(11) NOT NULL COMMENT 'links to academic_periods.id',
  `special_need_type_id` INT(11) NOT NULL COMMENT 'links to special_need_types.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`examination_centre_id`, `special_need_type_id`),
  KEY `examination_id` (`examination_id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the special needs for a particular examination centre';

CREATE TABLE `examination_centre_students` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_period.id',
  `examination_id` int(11) NOT NULL COMMENT 'links to examination.id',
  `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to `education_subjects.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`student_id`,`examination_centre_id`,`education_subject_id`),
  UNIQUE KEY `id` (`id`),
  KEY `institution_id` (`institution_id`),
  KEY `education_grade_id` (`education_grade_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `examination_id` (`examination_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the students registered to an examination center for a particular examination';

-- security_functions
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `order`, `visible`, `created_user_id`, `created`) VALUES (1045, 'Exams', 'Institutions', 'Institutions', 'Examinations', '1000', 'Exams.index|Exams.view', 1045, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (1046, 'Students', 'Institutions', 'Institutions', 'Examinations', '1000', 'ExaminationStudents.index|ExaminationStudents.view', null, 'ExaminationStudents.add', null, 'UndoExaminationRegistration.index|UndoExaminationRegistration.add|UndoExaminationRegistration.reconfirm|ExaminationStudents.unregister', 1046, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (5044, 'Exams', 'Examinations', 'Administration', 'Examinations', '5000', 'Exams.index|Exams.view', 'Exams.edit', 'Exams.add', 'Exams.remove', null, 5044, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (5045, 'Exam Centres', 'Examinations', 'Administration', 'Examinations', '5000', 'Centres.index|Centres.view', 'Centres.edit', 'Centres.add', 'Centres.remove', null, 5045, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (5046, 'Grading Types', 'Examinations', 'Administration', 'Examinations', '5000', 'GradingTypes.index|GradingTypes.view', 'GradingTypes.edit', 'GradingTypes.add', 'GradingTypes.remove', null, 5046, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (5047, 'Registered Students', 'Examinations', 'Administration', 'Examinations', '5000', 'RegisteredStudents.index|RegisteredStudents.view', null, null, null, 'RegisteredStudents.unregister', 5047, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (5048, 'Not Registered Students', 'Examinations', 'Administration', 'Examinations', '5000', 'NotRegisteredStudents.index|NotRegisteredStudents.view', null, null, null, null, 5048, 1, 1, NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('266f4853-80b3-11e6-a577-525400b263eb', 'ExaminationCentreNotRegisteredStudents', 'openemis_no', 'Examinations -> NotRegisteredStudents', 'OpenEMIS ID', 1, 1, NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('1d17a9f0-80b3-11e6-a577-525400b263eb', 'InstitutionExaminationStudents', 'openemis_no', 'Institution -> Examination -> Students', 'OpenEMIS ID', 1, 1, NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('0f930675-80b3-11e6-a577-525400b263eb', 'ExaminationCentreStudents', 'openemis_no', 'Examinations -> RegisteredStudents', 'OpenEMIS ID', 1, 1, NOW());


-- POCOR-3215
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3215', NOW());

-- import_mapping
UPDATE `import_mapping` SET `description` = 'Education Code' WHERE `import_mapping`.`id` = 15;


-- POCOR-3357
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3357', NOW());

-- rename `institution_providers` to a backup table
RENAME TABLE `institution_providers` TO `z_3357_institution_providers`;

-- recreate `institution_providers` with `institution_sector_id` column
DROP TABLE IF EXISTS `institution_providers`;
CREATE TABLE IF NOT EXISTS `institution_providers` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(50) NOT NULL,
 `order` int(3) NOT NULL,
 `visible` int(1) NOT NULL DEFAULT '1',
 `editable` int(1) NOT NULL DEFAULT '1',
 `default` int(1) NOT NULL DEFAULT '0',
 `institution_sector_id` int(11) NOT NULL COMMENT 'links to institution_sectors.id',
 `international_code` varchar(50) DEFAULT NULL,
 `national_code` varchar(50) DEFAULT NULL,
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This is a field option table containing the list of user-defined providers used by institutions';

INSERT INTO `institution_providers` (`id`, `name`, `order`, `visible`, `editable`, `default`, `institution_sector_id`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`, `order`, `visible`, `editable`, `default`, 0, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3357_institution_providers`;

-- replace `institution_sector_id` with the sectors from `institutions` that are linked to the providers
-- if no sector links to a particular provider in `institutions`, replace it with the default or first sector
UPDATE `institution_providers`
SET `institution_sector_id` = IFNULL((
    SELECT `institutions`.`institution_sector_id`
    FROM `institutions`
    WHERE `institutions`.`institution_provider_id` = `institution_providers`.`id`
    GROUP BY `institutions`.`institution_provider_id`
), IFNULL((SELECT `id` FROM `institution_sectors` WHERE `default` = 1), (SELECT `id` FROM `institution_sectors` LIMIT 1)));

-- replace `institution_sector_id` in `institutions` with the sectors that are linked to the providers in `institution_providers`
UPDATE `institutions`
SET `institution_sector_id` = (
    SELECT `institution_providers`.`institution_sector_id`
    FROM `institution_providers`
    WHERE `institutions`.`institution_provider_id` = `institution_providers`.`id`
    GROUP BY `institution_providers`.`id`
);

-- create label for sector
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('56e0a017-7bdc-11e6-92c7-525400b263eb', 'Providers', 'institution_sector_id', 'FieldOptions -> Providers', 'Sector', 1, 1, NOW());


-- POCOR-3347
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3347', NOW());

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT NULL, NULL, 'There are no shifts configured for the selected academic period', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual
WHERE NOT EXISTS (SELECT * FROM `translations` WHERE `en` = 'There are no shifts configured for the selected academic period');


-- 3.6.4
UPDATE config_items SET value = '3.6.4' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
