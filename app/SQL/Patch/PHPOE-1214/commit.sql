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

--
-- 5. data patch
--

DELIMITER $$

DROP PROCEDURE IF EXISTS patch1214
$$
CREATE PROCEDURE patch1214()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE educationProgrammeId, institutionSiteId, startYear INT;
  DECLARE startDate DATE;
  DECLARE isp CURSOR FOR SELECT `InstitutionSiteProgramme`.`education_programme_id`, `InstitutionSiteProgramme`.`institution_site_id`, MIN(`AcademicPeriod`.`start_date`) AS `start_date` FROM `1214_institution_site_programmes` AS `InstitutionSiteProgramme` JOIN `academic_periods` AS `AcademicPeriod` ON `AcademicPeriod`.`id` = `InstitutionSiteProgramme`.`academic_period_id` WHERE `InstitutionSiteProgramme`.`status` = 1 GROUP BY `InstitutionSiteProgramme`.`education_programme_id`, `InstitutionSiteProgramme`.`institution_site_id` ORDER BY `InstitutionSiteProgramme`.`institution_site_id`, `InstitutionSiteProgramme`.`education_programme_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN isp;
  TRUNCATE TABLE `institution_site_programmes`;

  read_loop: LOOP
    FETCH isp INTO educationProgrammeId, institutionSiteId, startDate;
    IF done THEN
      LEAVE read_loop;
    END IF;
    
    INSERT INTO `institution_site_programmes` (education_programme_id, institution_site_id, start_date, start_year, created_user_id, created) VALUES (educationProgrammeId, institutionSiteId, startDate, DATE_FORMAT(startDate, '%Y'), 1, NOW());

  END LOOP read_loop;

  CLOSE isp;
END
$$

CALL patch1214;
