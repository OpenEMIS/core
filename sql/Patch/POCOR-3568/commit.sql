-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3568', NOW());

-- `textbooks`
DROP TABLE IF EXISTS `textbooks`;
CREATE TABLE IF NOT EXISTS `textbooks` (
  `id` int(11) NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publisher` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year_published` int(4) NOT NULL,
  `ISBN` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_period.id',
  `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `textbooks`
  ADD PRIMARY KEY (`id`, `academic_period_id`),
  ADD KEY `academic_period_id` (`academic_period_id`),
  ADD KEY `education_grade_id` (`education_grade_id`),
  ADD KEY `education_subject_id` (`education_subject_id`);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `textbook_conditions`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `textbook_conditions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

INSERT INTO `textbook_conditions` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES
(1, 'New', 1, 1, 1, 0, '', '', NULL, NULL, 2, '2016-11-29 20:51:21'),
(2, 'Good', 2, 1, 1, 1, '', '', NULL, NULL, 2, '2016-11-29 20:51:35'),
(3, 'Poor', 3, 1, 1, 0, '', '', NULL, NULL, 2, '2016-11-29 20:51:44'),
(4, 'N/A', 4, 1, 1, 0, '', '', NULL, NULL, 2, '2016-11-29 20:52:00');


-- Table structure for table `Textbook_statuses`
DROP TABLE IF EXISTS `textbook_statuses`;
CREATE TABLE IF NOT EXISTS `textbook_statuses` (
  `id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `textbook_statuses`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `textbook_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

INSERT INTO `textbook_statuses` (`id`, `code`, `name`) 
VALUES 
(NULL, 'AVAILABLE', 'Available'), 
(NULL, 'NOT_AVAILABLE', 'Not Available');


-- Table structure for table `institution_textbooks`
DROP TABLE IF EXISTS `institution_textbooks`;
CREATE TABLE IF NOT EXISTS `institution_textbooks` (
  `id` int(11) NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `textbook_status_id` int(11) NULL COMMENT 'links to textbook_statuses.id',
  `textbook_condition_id` int(11) NULL COMMENT 'links to textbook_conditions.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_period.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
  `student_id` int(11) DEFAULT NULL COMMENT 'links to security_users.id',
  `textbook_id` int(11) NOT NULL COMMENT 'links to textbooks.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `institution_textbooks`
  ADD PRIMARY KEY (`id`,`academic_period_id`),
  ADD KEY `institution_id` (`institution_id`),
  ADD KEY `education_subject_id` (`education_subject_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `textbook_id` (`textbook_id`),
  ADD KEY `textbook_status_id` (`textbook_status_id`),
  ADD KEY `textbook_condition_id` (`textbook_condition_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

ALTER TABLE `institution_textbooks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES 
('7c85c025-b6e3-11e6-a3e3-525400b263eb', 'InstitutionTextbooks', 'student_id', 'Institutions -> Textbooks', 'Allocated To', NULL, NULL, '1', NULL, NULL, '1', '2016-11-30 00:00:00'),
('43653063-b6e5-11e6-a3e3-525400b263eb', 'InstitutionTextbooks', 'textbook_condition_id', 'Institutions -> Textbooks', 'Condition', NULL, NULL, '1', NULL, NULL, '1', '2016-11-30 00:00:00'), 
('43653db8-b6e5-11e6-a3e3-525400b263eb', 'InstitutionTextbooks', 'textbook_status_id', 'Institutions -> Textbooks', 'Status', NULL, NULL, '1', NULL, NULL, '1', '2016-11-30 00:00:00'),
('4497d103-b794-11e6-a3e3-525400b263eb', 'InstitutionTextbooks', 'code', 'Institutions -> Textbooks', 'Textbook ID', NULL, NULL, '1', NULL, NULL, '1', '2016-12-01 00:00:00');

-- security_functions
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES 
(5055, 'Textbooks', 'Textbooks', 'Administration', 'Textbooks', 5000, 'Textbooks.index|Textbooks.view', 'Textbooks.edit', 'Textbooks.add', 'Textbooks.remove', NULL, 5055, 1, NULL, NULL, NULL, 1, '2016-11-18 09:51:29'),
(1051, 'Textbooks', 'Institutions', 'Institutions', 'Academic', 1000, 'Textbooks.index|Textbooks.view', 'Textbooks.edit', 'Textbooks.add', 'Textbooks.remove', NULL, 1051, 1, NULL, NULL, NULL, 1, '2016-11-18 09:51:29'),
(6010, 'Textbooks', 'Reports', 'Reports', 'Reports', -1, 'Textbooks.index', NULL, 'Textbooks.add', NULL, 'Textbooks.download', 6003, 1, NULL, NULL, NULL, 1, '2016-12-02 00:00:00');

-- re-arrange order
UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `id` BETWEEN 6000 AND 7000
AND `order` >= 6003;