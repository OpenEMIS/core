--
-- 1. Backup - institution_site_programmes
--

CREATE TABLE IF NOT EXISTS `1214_institution_site_programmes` LIKE `institution_site_programmes`;

INSERT `1214_institution_site_programmes` SELECT * FROM `institution_site_programmes`;

DROP TABLE IF EXISTS `institution_site_programmes`;

--
-- 2. New table - institution_site_programmes
--

DROP TABLE IF EXISTS `institution_site_programmes`;
CREATE TABLE IF NOT EXISTS `institution_site_programmes` (
`id` int(11) NOT NULL,
  `education_programme_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `start_year` int(4) NOT NULL,
  `end_date` date DEFAULT NULL,
  `end_year` int(4) DEFAULT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_programmes`
 ADD PRIMARY KEY (`id`), ADD KEY `institution_site_id` (`institution_site_id`), ADD KEY `education_programme_id` (`education_programme_id`);


ALTER TABLE `institution_site_programmes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 3. New table - institution_site_grades
--

DROP TABLE IF EXISTS `institution_site_grades`;
CREATE TABLE IF NOT EXISTS `institution_site_grades` (
`id` int(11) NOT NULL,
  `status` int(1) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `institution_site_programme_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_grades`
 ADD PRIMARY KEY (`id`), ADD KEY `institution_site_id` (`institution_site_id`), ADD KEY `institution_site_programme_id` (`institution_site_programme_id`);


ALTER TABLE `institution_site_grades`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 4. Backup and drop columns - institution_site_students
--

CREATE TABLE IF NOT EXISTS `1214_institution_site_students` LIKE `institution_site_students`;

INSERT `1214_institution_site_students` SELECT * FROM `institution_site_students`;

ALTER TABLE `institution_site_students` DROP `institution_site_programme_id`;
