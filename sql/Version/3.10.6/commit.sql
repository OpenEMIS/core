-- POCOR-4081
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-4081', NOW());

ALTER TABLE `deleted_records`
RENAME TO `z_4081_deleted_records`;

CREATE TABLE `deleted_records` (
  `id` bigint(20) unsigned NOT NULL,
  `reference_table` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_key` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_date` int(8) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`,`deleted_date`),
  KEY `reference_table` (`reference_table`),
  KEY `deleted_date` (`deleted_date`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains data of previously deleted records'
PARTITION BY HASH (deleted_date)
PARTITIONS 101;

INSERT INTO `deleted_records`
SELECT `id`, `reference_table`, `reference_key`, `data`, date_format(created, '%Y%m%d') as `deleted_date`, `created_user_id`, `created`
FROM `z_4081_deleted_records`;


-- POCOR-4079
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-4079', NOW());

-- education_subjects_field_of_studies
DROP TABLE IF EXISTS `education_subjects_field_of_studies`;
CREATE TABLE IF NOT EXISTS `education_subjects_field_of_studies` (
    `id` char(64) NOT NULL,
    `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
    `education_field_of_study_id` int(11) NOT NULL COMMENT 'links to education_field_of_studies.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`education_subject_id`, `education_field_of_study_id`),
    KEY `education_subject_id` (`education_subject_id`),
    KEY `education_field_of_study_id` (`education_field_of_study_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains information about the subjects and field of studies ';

-- staff_qualifications
RENAME TABLE `staff_qualifications` TO `z_4079_staff_qualifications`;

DROP TABLE IF EXISTS `staff_qualifications`;
CREATE TABLE IF NOT EXISTS `staff_qualifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `document_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `graduate_year` int(4) DEFAULT NULL,
    `qualification_institution` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `gpa` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `file_name` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `file_content` longblob,
    `education_field_of_study_id` int(11) NOT NULL COMMENT 'links to education_field_of_studies.id',
    `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
    `qualification_title_id` int(11) NOT NULL COMMENT 'links to qualification_titles.id',
    `qualification_country_id` int(11) DEFAULT NULL COMMENT 'links to countries.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `education_field_of_study_id` (`education_field_of_study_id`),
    KEY `staff_id` (`staff_id`),
    KEY `qualification_title_id` (`qualification_title_id`),
    KEY `qualification_country_id` (`qualification_country_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains information about the qualification of the staff';

INSERT `staff_qualifications` (`id`, `document_no`, `graduate_year`, `qualification_institution`, `gpa`, `file_name`, `file_content`, `education_field_of_study_id`, `staff_id`, `qualification_title_id`, `qualification_country_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `document_no`, `graduate_year`, `qualification_institution`, `gpa`, `file_name`, `file_content`, 0, `staff_id`, `qualification_title_id`, `qualification_country_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_4079_staff_qualifications`;


-- POCOR-3941
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3941', NOW());

-- patch wrong modified dates
UPDATE `institution_class_students` SET `modified` = '1970-01-01 00:00:00' WHERE `modified` < '0001-01-01 00:00:00';

-- institution_class_students
CREATE TABLE `z_3941_institution_class_students` LIKE `institution_class_students`;

INSERT INTO `z_3941_institution_class_students`
SELECT `institution_class_students`.*
FROM `institution_class_students`
LEFT JOIN `institution_class_grades`
    ON `institution_class_students`.`institution_class_id` = `institution_class_grades`.`institution_class_id`
    AND `institution_class_grades`.`education_grade_id` = `institution_class_students`.`education_grade_id`
WHERE `institution_class_grades`.`id` IS NULL;

DELETE `institution_class_students`.*
FROM `institution_class_students`
LEFT JOIN `institution_class_grades`
    ON `institution_class_students`.`institution_class_id` = `institution_class_grades`.`institution_class_id`
    AND `institution_class_grades`.`education_grade_id` = `institution_class_students`.`education_grade_id`
WHERE `institution_class_grades`.`id` IS NULL;

-- institution_subject_students
CREATE TABLE `z_3941_institution_subject_students` LIKE `institution_subject_students`;

INSERT INTO `z_3941_institution_subject_students`
SELECT `institution_subject_students`.*
FROM `institution_subject_students`
LEFT JOIN `institution_class_grades`
    ON `institution_subject_students`.`institution_class_id` = `institution_class_grades`.`institution_class_id`
    AND `institution_class_grades`.`education_grade_id` = `institution_subject_students`.`education_grade_id`
where `institution_class_grades`.`id` IS NULL;

DELETE `institution_subject_students`.*
FROM `institution_subject_students`
LEFT JOIN `institution_class_grades` ON `institution_subject_students`.`institution_class_id` = `institution_class_grades`.`institution_class_id` AND `institution_class_grades`.`education_grade_id` = `institution_subject_students`.`education_grade_id`
where `institution_class_grades`.`id` IS NULL;


-- 3.10.6
UPDATE config_items SET value = '3.10.6' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
