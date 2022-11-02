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
