-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2784', NOW());

-- backup the old table
ALTER TABLE `institution_subject_students` 
RENAME TO  `z_2784_institution_subject_students`;

CREATE TABLE IF NOT EXISTS `institution_subject_students` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(1) NOT NULL,
  `total_mark` decimal(6,2) DEFAULT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_subject_id` int(11) NOT NULL,
  `institution_class_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `education_subject_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `institution_subject_students`
--
ALTER TABLE `institution_subject_students`
  ADD PRIMARY KEY (`student_id`,`institution_class_id`,`institution_id`,`academic_period_id`,`education_subject_id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `institution_subject_id` (`institution_subject_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

-- reinsert from backup table

INSERT INTO institution_subject_students
SELECT 
	`z_2784_institution_subject_students`.`id`,
	`z_2784_institution_subject_students`.`status`, 
	`z_2784_institution_subject_students`.`total_mark`, 
	`z_2784_institution_subject_students`.`student_id`, 
	`z_2784_institution_subject_students`.`institution_subject_id`,
	`z_2784_institution_subject_students`.`institution_class_id`, 
	`z_2784_institution_subject_students`.`institution_id`, 
	`z_2784_institution_subject_students`.`academic_period_id`, 
	`z_2784_institution_subject_students`.`education_subject_id`,
	`z_2784_institution_subject_students`.`modified_user_id`,
	`z_2784_institution_subject_students`.`modified`,
	`z_2784_institution_subject_students`.`created_user_id`, 
	`z_2784_institution_subject_students`.`created`
FROM `z_2784_institution_subject_students`;


-- patch the existing data.

-- remove duplicate record if there is any. 1 same education_subject_id with multiple education_subject_id
DELETE n1 
FROM `institution_subject_students` n1, `institution_subject_students` n2 
WHERE n1.`education_subject_id` > n2.`education_subject_id`
AND n1.`student_id` = n2.`student_id`
AND n1.`institution_subject_id` = n2.`institution_subject_id`
AND n1.`institution_class_id` = n2.`institution_class_id`
AND n1.`institution_id` = n2.`institution_id`
AND n1.`academic_period_id` = n2.`academic_period_id`;

-- update to the right value
UPDATE `institution_subject_students` SS
INNER JOIN `institution_subjects` S ON S.id = SS.`institution_subject_id`
SET SS.`education_subject_id` = S.`education_subject_id`;

