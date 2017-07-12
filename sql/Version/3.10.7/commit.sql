-- POCOR-3877
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3877', NOW());

ALTER TABLE `security_role_functions`
RENAME TO  `z_3877_security_role_functions` ;

CREATE TABLE `security_role_functions` (
  `_view` int(1) DEFAULT '1',
  `_edit` int(1) DEFAULT '0',
  `_add` int(1) DEFAULT '0',
  `_delete` int(1) DEFAULT '0',
  `_execute` int(1) DEFAULT '0',
  `security_role_id` int(11) NOT NULL COMMENT 'links to security_roles.id',
  `security_function_id` int(11) NOT NULL COMMENT 'links to security_functions.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`security_role_id`,`security_function_id`),
  KEY `security_function_id` (`security_function_id`),
  KEY `security_role_id` (`security_role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of functions that can be accessed by the roles';

INSERT IGNORE INTO `security_role_functions` (`_view`, `_edit`, `_add`, `_delete`, `_execute`, `security_role_id`, `security_function_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `_view`, `_edit`, `_add`, `_delete`, `_execute`, `security_role_id`, `security_function_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3877_security_role_functions`;


-- POCOR-4075
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-4075', NOW());

-- reports
ALTER TABLE `reports`
 MODIFY COLUMN `excel_template_name` varchar(250) COLLATE utf8mb4_unicode_ci NULL,
 MODIFY COLUMN `excel_template` longblob NULL,
 MODIFY COLUMN `format` int(1) NOT NULL DEFAULT 1 COMMENT '1 -> CSV, 2 -> XLSX';

-- report_progress
ALTER TABLE `report_progress`
 ADD COLUMN `sql` text COLLATE utf8_general_ci NULL AFTER `params`;


-- POCOR-3995
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


-- 3.10.7
UPDATE config_items SET value = '3.10.7' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
