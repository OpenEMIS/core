-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3568', NOW());

-- `textbooks`
DROP TABLE IF EXISTS `textbooks`;
CREATE TABLE IF NOT EXISTS `textbooks` (
  `id` int(11) NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_bin NULL,
  `title` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `author` varchar(100) COLLATE utf8mb4_bin NULL,
  `publisher` varchar(100) COLLATE utf8mb4_bin NULL,
  `year` year(4) NULL,
  `ISBN` varchar(100) COLLATE utf8mb4_bin NULL,
  `provider` varchar(100) COLLATE utf8mb4_bin NULL,
  `visible` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'link to links to academic_period.id',
  `education_programme_id` int(11) NOT NULL COMMENT 'link to links to education_programmes.id',
  `education_grade_id` int(11) NOT NULL COMMENT 'link to links to education_grades.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'link to links to education_subjects.id',
  `previous_textbook_id` int(11) NULL COMMENT 'link to links to textbooks.id',
  `modified_user_id` int(11) NULL,
  `modified` datetime NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

ALTER TABLE `textbooks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academic_period_id` (`academic_period_id`),
  ADD KEY `education_programme_id` (`education_programme_id`),
  ADD KEY `education_grade_id` (`education_grade_id`),
  ADD KEY `education_subject_id` (`education_subject_id`),
  ADD KEY `previous_textbook_id` (`previous_textbook_id`);

ALTER TABLE `textbooks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


-- `textbook_conditions`
DROP TABLE IF EXISTS `textbook_conditions`;
CREATE TABLE IF NOT EXISTS `textbook_conditions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `textbook_conditions`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `textbook_conditions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


-- Table structure for table `Textbook_statuses`

DROP TABLE IF EXISTS `Textbook_statuses`;
CREATE TABLE IF NOT EXISTS `Textbook_statuses` (
  `id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `Textbook_statuses`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `Textbook_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


-- `institution_textbooks`
DROP TABLE IF EXISTS `institution_textbooks`;
CREATE TABLE IF NOT EXISTS `institution_textbooks` (
  `id` int(11) NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `comment` varchar(255) COLLATE utf8mb4_bin,
  `textbook_status_id` int(11) NOT NULL COMMENT 'link to links to textbook_statuses.id',
  `textbook_condition_id` int(11) NOT NULL COMMENT 'link to links to textbook_conditions.id',
  `institution_id` int(11) NOT NULL COMMENT 'link to links to institutions.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'link to links to academic_period.id',
  `education_subject_id` int(11) DEFAULT NULL COMMENT 'links to education_subjects.id',
  `student_id` int(11) DEFAULT NULL COMMENT 'links to security_users.id',
  `textbook_id` int(11) NOT NULL COMMENT 'links to textbooks.id',
  `modified_user_id` int(11) NULL,
  `modified` datetime NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

ALTER TABLE `institution_textbooks`
  ADD PRIMARY KEY (`id`, `academic_period_id`),
  ADD KEY `institution_id` (`institution_id`),
  -- ADD KEY `academic_period_id` (`academic_period_id`),
  ADD KEY `education_subject_id` (`education_subject_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `textbook_id` (`textbook_id`),
  ADD KEY `textbook_status_id` (`textbook_status_id`),
  ADD KEY `textbook_condition_id` (`textbook_condition_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

ALTER TABLE `institution_textbooks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
