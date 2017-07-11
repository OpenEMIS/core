-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3995', NOW());

-- education_grades_subjects
RENAME TABLE `education_grades_subjects` TO `z_3995_education_grades_subjects`;

DROP TABLE IF EXISTS `education_grades_subjects`;
CREATE TABLE IF NOT EXISTS `education_grades_subjects` (
    `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
    `hours_required` decimal(5,2) DEFAULT NULL,
    `visible` int(1) NOT NULL DEFAULT '1',
    `auto_allocation` int(1) NOT NULL DEFAULT '1',
    `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
    `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`education_grade_id`,`education_subject_id`),
    KEY `education_grade_id` (`education_grade_id`),
    KEY `education_subject_id` (`education_subject_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of subjects linked to specific education grade';

INSERT IGNORE INTO `education_grades_subjects` (`id`, `hours_required`, `visible`, `auto_allocation`, `education_grade_id`, `education_subject_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT SHA2(CONCAT(`education_grade_id`, `education_subject_id`), 256), `hours_required`, `visible`, 1, `education_grade_id`, `education_subject_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3995_education_grades_subjects`;
