-- POCOR-3870
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3870', NOW());

-- institution_subject_students
CREATE TABLE IF NOT EXISTS `institution_subject_students_temp` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_mark` decimal(6,2) DEFAULT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_subject_id` int(11) NOT NULL COMMENT 'links to institution_subjects.id',
  `institution_class_id` int(11) NOT NULL COMMENT 'links to institution_classes.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
  `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
  `student_status_id` int(11) NOT NULL COMMENT 'links to student_statuses.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of students attending the subjects';

ALTER TABLE `institution_subject_students_temp`
  ADD PRIMARY KEY (`student_id`,`institution_class_id`,`institution_id`,`academic_period_id`,`education_subject_id`,`education_grade_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `institution_subject_id` (`institution_subject_id`),
  ADD KEY `institution_class_id` (`institution_class_id`),
  ADD KEY `institution_id` (`institution_id`),
  ADD KEY `academic_period_id` (`academic_period_id`),
  ADD KEY `education_subject_id` (`education_subject_id`),
  ADD KEY `education_grade_id` (`education_grade_id`),
  ADD KEY `student_status_id` (`student_status_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`),
  ADD KEY `id` (`id`);

INSERT INTO `institution_subject_students_temp`
SELECT SHA2(CONCAT(`student_id`,',',`institution_subject_id`,',',`institution_class_id`,',',`institution_id`,',',`academic_period_id`,',',`education_subject_id`,',',`education_grade_id`), 256),
`total_mark`, `student_id`, `institution_subject_id`, `institution_class_id`, `institution_id`, `academic_period_id`, `education_subject_id`, `education_grade_id`, `student_status_id`,
`modified_user_id`, `modified`, `created_user_id`, `created`
FROM `institution_subject_students`;

RENAME TABLE `institution_subject_students` TO `z_3870_institution_subject_students_1`;

RENAME TABLE `institution_subject_students_temp` TO `institution_subject_students`;


-- 3.9.10
UPDATE config_items SET value = '3.9.10' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
