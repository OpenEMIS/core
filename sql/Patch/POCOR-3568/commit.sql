-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3568', NOW());

-- `textbooks`
DROP TABLE IF EXISTS `textbooks`;
CREATE TABLE IF NOT EXISTS `textbooks` (
  `id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'link to links to academic_period.id',
  `education_programme_id` int(11) NOT NULL COMMENT 'link to links to education_programmes.id',
  `education_grade_id` int(11) NOT NULL COMMENT 'link to links to education_grades.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'link to links to education_subjects.id',
  `code` varchar(50) COLLATE utf8mb4_bin NULL,
  `name` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `author` varchar(100) COLLATE utf8mb4_bin NULL,
  `publisher` varchar(100) COLLATE utf8mb4_bin NULL,
  `year` year(4) NULL,
  `ISBN` varchar(100) COLLATE utf8mb4_bin NULL,
  `provider` varchar(100) COLLATE utf8mb4_bin NULL,
  `visible` int(11) NOT NULL,
  `modified_user_id` int(11) NULL,
  `modified` datetime NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

ALTER TABLE `textbooks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academic_period_id` (`academic_period_id`),
  ADD KEY `education_grade_id` (`education_grade_id`),
  ADD KEY `education_subject_id` (`education_subject_id`);

ALTER TABLE `textbooks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;