-- 30th July 2015

DROP TABLE IF EXISTS `institution_grade_students`;
CREATE TABLE IF NOT EXISTS `institution_grade_students` (
  `id` char(36) NOT NULL,
  `student_status_id` int(11) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `institution_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_grade_students`
  ADD PRIMARY KEY (`id`), ADD KEY `security_user_id` (`security_user_id`), ADD KEY `education_grade_id` (`education_grade_id`), ADD KEY `academic_period_id` (`academic_period_id`), ADD KEY `institution_id` (`institution_id`);

-- patch institution_site_grades
ALTER TABLE `institution_site_grades` DROP `status`;
ALTER TABLE `institution_site_grades` ADD `start_date` DATE NOT NULL AFTER `education_grade_id`, ADD `start_year` INT(4) NOT NULL AFTER `start_date`, ADD `end_date` DATE NULL AFTER `start_year`, ADD `end_year` INT(4) NULL AFTER `end_date`;

UPDATE `institution_site_grades` 
JOIN `institution_site_programmes` ON `institution_site_programmes`.`id` = `institution_site_grades`.`institution_site_programme_id`
SET `institution_site_grades`.`start_date` = `institution_site_programmes`.`start_date`,
`institution_site_grades`.`start_year` = YEAR(`institution_site_programmes`.`start_date`),
`institution_site_grades`.`end_date` = `institution_site_programmes`.`end_date`,
`institution_site_grades`.`end_year` = YEAR(`institution_site_programmes`.`end_year`);

-- insert student_statuses
INSERT INTO `student_statuses` (`code`, `name`) VALUES
('PROMOTED', 'Promoted'),
('REPEATED', 'Repeated');

-- patch institution_grade_students
TRUNCATE TABLE `institution_grade_students`;

DELIMITER $$

DROP PROCEDURE IF EXISTS student_promotion
$$
CREATE PROCEDURE student_promotion()
BEGIN
	DECLARE done INT DEFAULT FALSE;
	DECLARE studentId, gradeId, periodId, institutionId INT(11);
	DECLARE startDate, endDate date;
	DECLARE student CURSOR FOR 
		SELECT `SectionStudents`.`security_user_id`, `SectionStudents`.`education_grade_id`, `Sections`.`academic_period_id`, `Sections`.`institution_site_id`, `Periods`.`start_date`, `Periods`.`end_date`
		FROM `institution_site_section_students` AS `SectionStudents`
		INNER JOIN `institution_site_sections` AS `Sections` ON `Sections`.`id` = `SectionStudents`.`institution_site_section_id`
		INNER JOIN `institution_site_grades` AS `Grades` ON `Grades`.`education_grade_id` = `SectionStudents`.`education_grade_id`
		INNER JOIN `academic_periods` AS `Periods` ON `Periods`.`id` = `Sections`.`academic_period_id`
		GROUP BY `SectionStudents`.`security_user_id`, `SectionStudents`.`education_grade_id`, `Sections`.`academic_period_id`, `Sections`.`institution_site_id`;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

	OPEN student;

	read_loop: LOOP
	FETCH student INTO studentId, gradeId, periodId, institutionId, startDate, endDate;
	IF done THEN
		LEAVE read_loop;
	END IF;

		INSERT INTO `institution_grade_students` (`id`, `student_status_id`, `security_user_id`, `education_grade_id`, `academic_period_id`, `start_date`, `end_date`, `institution_id`) VALUES (uuid(), 1, studentId, gradeId, periodId, startDate, endDate, institutionId);

	END LOOP read_loop;

	CLOSE student;
END
$$

CALL student_promotion
$$

DROP PROCEDURE IF EXISTS student_promotion
$$

DELIMITER ;
