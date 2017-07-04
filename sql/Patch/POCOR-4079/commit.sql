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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains information about the subjects of any class';
