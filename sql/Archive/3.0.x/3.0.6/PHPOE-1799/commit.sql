-- 30th July 2015
INSERT INTO `db_patches` VALUES ('PHPOE-1799');

-- institution_student_transfers
ALTER TABLE `institution_student_transfers` ADD `academic_period_id` INT(11) NOT NULL AFTER `institution_id`;
ALTER TABLE `institution_student_transfers` CHANGE `education_programme_id` `education_grade_id` INT(11) NOT NULL;
ALTER TABLE `institution_student_transfers` CHANGE `comment` `comment` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

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
INSERT INTO `student_statuses` (`id`, `code`, `name`) VALUES
(7, 'PROMOTED', 'Promoted'),
(8, 'REPEATED', 'Repeated');

-- new security_user_types table for saving different types of the same user
DROP TABLE IF EXISTS `security_user_types`;
CREATE TABLE IF NOT EXISTS `security_user_types` (
  `security_user_id` int(11) NOT NULL,
  `user_type` int(1) NOT NULL COMMENT '1 -> STUDENT, 2 -> STAFF, 3 -> GUARDIAN',
  PRIMARY KEY (`security_user_id`, `user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `security_user_types` SELECT `security_user_id`, 1 FROM `institution_site_students` GROUP BY `security_user_id`;
INSERT INTO `security_user_types` SELECT `security_user_id`, 2 FROM `institution_site_staff` GROUP BY `security_user_id`;
INSERT INTO `security_user_types` SELECT `guardian_user_id`, 3 FROM `student_guardians` GROUP BY `guardian_user_id`;

-- institution_students
DROP TABLE IF EXISTS `institution_students`;
CREATE TABLE IF NOT EXISTS `institution_students` (
  `id` char(36) NOT NULL,
  `student_status_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `education_grade_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `start_year` int(4) NOT NULL,
  `end_date` date DEFAULT NULL,
  `end_year` int(4) DEFAULT NULL,
  `institution_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_students`
  ADD PRIMARY KEY (`id`), ADD KEY `student_id` (`student_id`), ADD KEY `education_grade_id` (`education_grade_id`), ADD KEY `academic_period_id` (`academic_period_id`), ADD KEY `institution_id` (`institution_id`);

-- patch institution_students
TRUNCATE TABLE `institution_students`;

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

		INSERT INTO `institution_students` (`id`, `student_status_id`, `student_id`, `education_grade_id`, `academic_period_id`, `start_date`, `end_date`, `start_year`, `end_year`, `institution_id`, `created_user_id`, `created`) VALUES (uuid(), 1, studentId, gradeId, periodId, startDate, endDate, YEAR(startDate), YEAR(endDate), institutionId, 1, NOW());

	END LOOP read_loop;

	CLOSE student;
END
$$

CALL student_promotion
$$

DROP PROCEDURE IF EXISTS student_promotion
$$

DELIMITER ;
