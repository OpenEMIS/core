ALTER TABLE `db_patches` ADD `version` VARCHAR(15) NULL AFTER `issue`;

set @earliest := 0;
select min(created) into @earliest from db_patches where created <> '0000-00-00 00:00:00';
update db_patches set created = date(@earliest) where created = '0000-00-00 00:00:00';

INSERT INTO db_patches (issue, version, created) VALUES ('PHPOE-1347', '3.2.2', @earliest);

UPDATE db_patches SET version = '3.4.18.2' WHERE issue = 'POCOR-2786';
UPDATE db_patches SET version = '3.5.1' WHERE issue = 'POCOR-2172';
UPDATE db_patches SET version = '3.4.18' WHERE issue = 'POCOR-2749';
UPDATE db_patches SET version = '3.4.18' WHERE issue = 'POCOR-2675';
UPDATE db_patches SET version = '3.4.18' WHERE issue = 'POCOR-1694';
UPDATE db_patches SET version = '3.4.18' WHERE issue = 'POCOR-2733';
UPDATE db_patches SET version = '3.4.17' WHERE issue = 'POCOR-2604';
UPDATE db_patches SET version = '3.4.16' WHERE issue = 'POCOR-2658';
UPDATE db_patches SET version = '3.4.16' WHERE issue = 'POCOR-2609';
UPDATE db_patches SET version = '3.4.16' WHERE issue = 'POCOR-1905';
UPDATE db_patches SET version = '3.4.16' WHERE issue = 'POCOR-2208';
UPDATE db_patches SET version = '3.4.16' WHERE issue = 'POCOR-2540';
UPDATE db_patches SET version = '3.4.16' WHERE issue = 'POCOR-2562';
UPDATE db_patches SET version = '3.4.16' WHERE issue = 'POCOR-1798';
UPDATE db_patches SET version = '3.4.15a' WHERE issue = 'POCOR-2683';
UPDATE db_patches SET version = '3.4.15a' WHERE issue = 'POCOR-2612';
UPDATE db_patches SET version = '3.4.15' WHERE issue = 'POCOR-2608';
UPDATE db_patches SET version = '3.4.15' WHERE issue = 'POCOR-2446';
UPDATE db_patches SET version = '3.4.15' WHERE issue = 'POCOR-2601';
UPDATE db_patches SET version = '3.4.15' WHERE issue = 'POCOR-2445';
UPDATE db_patches SET version = '3.4.14' WHERE issue = 'POCOR-2564';
UPDATE db_patches SET version = '3.4.14' WHERE issue = 'POCOR-2571';
UPDATE db_patches SET version = '3.4.14' WHERE issue = 'POCOR-2014';
UPDATE db_patches SET version = '3.4.14' WHERE issue = 'POCOR-1968';
UPDATE db_patches SET version = '3.4.14' WHERE issue = 'POCOR-2491';
UPDATE db_patches SET version = '3.4.13' WHERE issue = 'POCOR-2539';
UPDATE db_patches SET version = '3.4.13' WHERE issue = 'PHPOE-2535';
UPDATE db_patches SET version = '3.4.13' WHERE issue = 'POCOR-2489';
UPDATE db_patches SET version = '3.4.13' WHERE issue = 'POCOR-2515';
UPDATE db_patches SET version = '3.4.13' WHERE issue = 'POCOR-2526';
UPDATE db_patches SET version = '3.4.13' WHERE issue = 'POCOR-2392';
UPDATE db_patches SET version = '3.4.12' WHERE issue = 'POCOR-2497';
UPDATE db_patches SET version = '3.4.12' WHERE issue = 'POCOR-2501';
UPDATE db_patches SET version = '3.4.12' WHERE issue = 'POCOR-2465';
UPDATE db_patches SET version = '3.4.12' WHERE issue = 'POCOR-2232';
UPDATE db_patches SET version = '3.4.12' WHERE issue = 'POCOR-2506';
UPDATE db_patches SET version = '3.4.11' WHERE issue = 'PHPOE-1508';
UPDATE db_patches SET version = '3.4.11' WHERE issue = 'PHPOE-2484';
UPDATE db_patches SET version = '3.4.11' WHERE issue = 'PHPOE-2500';
UPDATE db_patches SET version = '3.4.11' WHERE issue = 'PHPOE-2505';
UPDATE db_patches SET version = '3.4.10' WHERE issue = 'PHPOE-2168';
UPDATE db_patches SET version = '3.4.10' WHERE issue = 'PHPOE-2423';
UPDATE db_patches SET version = '3.4.10' WHERE issue = 'PHPOE-2433';
UPDATE db_patches SET version = '3.4.9' WHERE issue = 'PHPOE-2436';
UPDATE db_patches SET version = '3.4.9' WHERE issue = 'PHPOE-2463';
UPDATE db_patches SET version = '3.4.9' WHERE issue = 'PHPOE-2023';
UPDATE db_patches SET version = '3.4.9' WHERE issue = 'PHPOE-1787';
UPDATE db_patches SET version = '3.4.8' WHERE issue = 'PHPOE-2435';
UPDATE db_patches SET version = '3.4.8' WHERE issue = 'PHPOE-2338';
UPDATE db_patches SET version = '3.4.7' WHERE issue = 'PHPOE-2291';
UPDATE db_patches SET version = '3.4.7' WHERE issue = 'PHPOE-832';
UPDATE db_patches SET version = '3.4.7' WHERE issue = 'PHPOE-1227';
UPDATE db_patches SET version = '3.4.7' WHERE issue = 'PHPOE-1808';
UPDATE db_patches SET version = '3.4.6' WHERE issue = 'PHPOE-2421';
UPDATE db_patches SET version = '3.4.5' WHERE issue = 'PHPOE-1903';
UPDATE db_patches SET version = '3.4.5' WHERE issue = 'PHPOE-2198';
UPDATE db_patches SET version = '3.4.4' WHERE issue = 'PHPOE-2403';
UPDATE db_patches SET version = '3.4.3' WHERE issue = 'PHPOE-1420';
UPDATE db_patches SET version = '3.4.2' WHERE issue = 'PHPOE-2366';
UPDATE db_patches SET version = '3.4.2' WHERE issue = 'PHPOE-2319';
UPDATE db_patches SET version = '3.4.2' WHERE issue = 'PHPOE-2359';
UPDATE db_patches SET version = '3.4.2' WHERE issue = 'PHPOE-1961';
UPDATE db_patches SET version = '3.4.1' WHERE issue = 'PHPOE-2257';
UPDATE db_patches SET version = '3.4.1' WHERE issue = 'PHPOE-2193';
UPDATE db_patches SET version = '3.4.1' WHERE issue = 'PHPOE-1463';
UPDATE db_patches SET version = '3.3.8' WHERE issue = 'PHPOE-2298';
UPDATE db_patches SET version = '3.3.8' WHERE issue = 'PHPOE-2310';
UPDATE db_patches SET version = '3.3.8' WHERE issue = 'PHPOE-2250';
UPDATE db_patches SET version = '3.3.7' WHERE issue = 'PHPOE-2069';
UPDATE db_patches SET version = '3.3.7' WHERE issue = 'PHPOE-2086';
UPDATE db_patches SET version = '3.3.7' WHERE issue = 'PHPOE-1978';
UPDATE db_patches SET version = '3.3.6' WHERE issue = 'PHPOE-1707';
UPDATE db_patches SET version = '3.3.5' WHERE issue = 'PHPOE-1902-2';
UPDATE db_patches SET version = '3.3.4' WHERE issue = 'PHPOE-2084';
UPDATE db_patches SET version = '3.3.4' WHERE issue = 'PHPOE-2099';
UPDATE db_patches SET version = '3.3.4' WHERE issue = 'PHPOE-2281';
UPDATE db_patches SET version = '3.3.4' WHERE issue = 'PHPOE-2305';
UPDATE db_patches SET version = '3.3.1' WHERE issue = 'PHPOE-2248';
UPDATE db_patches SET version = '3.3.1' WHERE issue = 'PHPOE-1992';
UPDATE db_patches SET version = '3.2.10' WHERE issue = 'PHPOE-2233';
UPDATE db_patches SET version = '3.2.7' WHERE issue = 'PHPOE-680';
UPDATE db_patches SET version = '3.2.6' WHERE issue = 'PHPOE-2225';
UPDATE db_patches SET version = '3.2.6' WHERE issue = 'PHPOE-1352';
UPDATE db_patches SET version = '3.2.4' WHERE issue = 'PHPOE-2092';
UPDATE db_patches SET version = '3.2.4' WHERE issue = 'PHPOE-2178';
UPDATE db_patches SET version = '3.2.4' WHERE issue = 'PHPOE-1904';
UPDATE db_patches SET version = '3.2.5' WHERE issue = 'PHPOE-2081';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-2144';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-2124';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-2078';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-2028';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-2103';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-1430';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-1414';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-1381';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-1346';
UPDATE db_patches SET version = '3.1.5' WHERE issue = 'PHPOE-1391';
UPDATE db_patches SET version = '3.1.4' WHERE issue = 'PHPOE-1573';
UPDATE db_patches SET version = '3.0.9' WHERE issue = 'PHPOE-1592';
UPDATE db_patches SET version = '3.0.9' WHERE issue = 'PHPOE-1657';
UPDATE db_patches SET version = '3.1.1' WHERE issue = 'PHPOE-1741';
UPDATE db_patches SET version = '3.0.6' WHERE issue = 'PHPOE-1762';
UPDATE db_patches SET version = '3.0.6' WHERE issue = 'PHPOE-1799';
UPDATE db_patches SET version = '3.1.3' WHERE issue = 'PHPOE-1807';
UPDATE db_patches SET version = '3.0.6' WHERE issue = 'PHPOE-1815';
UPDATE db_patches SET version = '3.0.6' WHERE issue = 'PHPOE-1821';
UPDATE db_patches SET version = '3.0.8' WHERE issue = 'PHPOE-1825';
UPDATE db_patches SET version = '3.0.9' WHERE issue = 'PHPOE-1857';
UPDATE db_patches SET version = '3.0.9' WHERE issue = 'PHPOE-1878';
UPDATE db_patches SET version = '3.0.8' WHERE issue = 'PHPOE-1882';
UPDATE db_patches SET version = '3.1.5' WHERE issue = 'PHPOE-1892';
UPDATE db_patches SET version = '3.1.3' WHERE issue = 'PHPOE-1896';
UPDATE db_patches SET version = '3.2.1' WHERE issue = 'PHPOE-1900';
UPDATE db_patches SET version = '3.2.1' WHERE issue = 'PHPOE-1902';
UPDATE db_patches SET version = '3.2.1' WHERE issue = 'PHPOE-1916';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-1919';
UPDATE db_patches SET version = '3.2.1' WHERE issue = 'PHPOE-1933';
UPDATE db_patches SET version = '3.1.2' WHERE issue = 'PHPOE-1948';
UPDATE db_patches SET version = '3.2.1' WHERE issue = 'PHPOE-1982';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-2016';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-2019';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-2036';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-2063';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-2072';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-2088';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-2117';


-- POCOR-2759
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2759', NOW());

-- assessments
RENAME TABLE `assessments` TO `z_2759_assessments`;

DROP TABLE IF EXISTS `assessments`;
CREATE TABLE IF NOT EXISTS `assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '1 -> Non-official, 2 -> Official',
  `academic_period_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `education_grade_id` (`education_grade_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `assessments` (`id`, `code`, `name`, `description`, `type`, `academic_period_id`, `education_grade_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `code`, `name`, `description`, `type`, 0, `education_grade_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_2759_assessments`;

UPDATE `assessments` SET `type` = 2 WHERE `type` = 1;
UPDATE `assessments` SET `type` = 1 WHERE `type` = 0;

-- assessment_items
RENAME TABLE `assessment_items` TO `z_2759_assessment_items`;

DROP TABLE IF EXISTS `assessment_items`;
CREATE TABLE IF NOT EXISTS `assessment_items` (
  `id` char(36) NOT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `assessment_grading_type_id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `education_subject_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `assessment_grading_type_id` (`assessment_grading_type_id`),
  INDEX `assessment_id` (`assessment_id`),
  INDEX `education_subject_id` (`education_subject_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `assessment_items` (`id`, `weight`, `assessment_grading_type_id`, `assessment_id`, `education_subject_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(), NULL, `assessment_grading_type_id`, `assessment_id`, `education_subject_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_2759_assessment_items`;

-- assessment_item_results
RENAME TABLE `assessment_item_results` TO `z_2759_assessment_item_results`;

DROP TABLE IF EXISTS `assessment_item_results`;
CREATE TABLE IF NOT EXISTS `assessment_item_results` (
  `id` char(36) NOT NULL,
  `marks` decimal(6,2) DEFAULT NULL,
  `assessment_grading_option_id` int(11) DEFAULT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `assessment_id` int(11) NOT NULL,
  `education_subject_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `assessment_period_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`student_id`,`assessment_id`,`education_subject_id`,`institution_id`,`academic_period_id`,`assessment_period_id`),
  INDEX `assessment_grading_option_id` (`assessment_grading_option_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PARTITION BY HASH(`academic_period_id`) PARTITIONS 8;

INSERT INTO `assessment_item_results` (`id`, `marks`, `assessment_grading_option_id`, `student_id`, `assessment_id`, `education_subject_id`, `institution_id`, `academic_period_id`, `assessment_period_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(), `AssessmentItemResults`.`marks`, `AssessmentItemResults`.`assessment_grading_option_id`, `AssessmentItemResults`.`student_id`, `AssessmentItems`.`assessment_id`, `AssessmentItems`.`education_subject_id`, `AssessmentItemResults`.`institution_id`, `AssessmentItemResults`.`academic_period_id`, 0, `AssessmentItemResults`.`modified_user_id`, `AssessmentItemResults`.`modified`, `AssessmentItemResults`.`created_user_id`, `AssessmentItemResults`.`created`
FROM `z_2759_assessment_item_results` AS `AssessmentItemResults`
INNER JOIN `z_2759_assessment_items` AS `AssessmentItems`
ON `AssessmentItems`.`id` = `AssessmentItemResults`.`assessment_item_id`
GROUP BY 
  `AssessmentItemResults`.`student_id`, 
  `AssessmentItems`.`assessment_id`,
  `AssessmentItems`.`education_subject_id`,
  `AssessmentItemResults`.`institution_id`,
  `AssessmentItemResults`.`academic_period_id`
;

-- assessment_grading_types
RENAME TABLE `assessment_grading_types` TO `z_2759_assessment_grading_types`;

DROP TABLE IF EXISTS `assessment_grading_types`;
CREATE TABLE IF NOT EXISTS `assessment_grading_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  `pass_mark` decimal(6,2) NOT NULL, 
  `max` decimal(6,2) NOT NULL,
  `result_type` varchar(20) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `code` (`code`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `assessment_grading_types` (`id`, `code`, `name`, `pass_mark`, `max`, `result_type`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `code`, `name`, 0, 0, '', `visible`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_2759_assessment_grading_types`;

-- assessment_grading_options
RENAME TABLE `assessment_grading_options` TO `z_2759_assessment_grading_options`;

DROP TABLE IF EXISTS `assessment_grading_options`;
CREATE TABLE IF NOT EXISTS `assessment_grading_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  `min` decimal(6,2) DEFAULT NULL,
  `max` decimal(6,2) DEFAULT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `assessment_grading_type_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `assessment_grading_type_id` (`assessment_grading_type_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `assessment_grading_options` SELECT * FROM `z_2759_assessment_grading_options`;

-- assessment_periods
DROP TABLE IF EXISTS `assessment_periods`;
CREATE TABLE IF NOT EXISTS `assessment_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `date_enabled` date NOT NULL,
  `date_disabled` date NOT NULL,
  `weight` decimal(6,2) NULL DEFAULT NULL,
  `assessment_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `assessment_id` (`assessment_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup tables
RENAME TABLE `assessment_statuses` TO `z_2759_assessment_statuses`;
RENAME TABLE `assessment_status_periods` TO `z_2759_assessment_status_periods`;
RENAME TABLE `institution_assessments` TO `z_2759_institution_assessments`;

-- institution_subjects
ALTER TABLE `institution_subjects` ADD INDEX(`education_subject_id`);

-- institution_subject_students
RENAME TABLE `institution_subject_students` TO `z_2759_institution_subject_students`;

DROP TABLE IF EXISTS `institution_subject_students`;
CREATE TABLE `institution_subject_students` (
  `id` char(36) NOT NULL,
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
  `created` datetime NOT NULL,
  PRIMARY KEY (`student_id`,`institution_class_id`,`institution_id`,`academic_period_id`,`education_subject_id`),
  UNIQUE (`id`),
  INDEX `institution_subject_id` (`institution_subject_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `institution_subject_students` (`id`, `status`, `total_mark`, `student_id`, `institution_subject_id`, `institution_class_id`, `institution_id`, `academic_period_id`, `education_subject_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(), `InstitutionSubjectStudents`.`status`, NULL, `InstitutionSubjectStudents`.`student_id`, `InstitutionSubjectStudents`.`institution_subject_id`, `InstitutionSubjectStudents`.`institution_class_id`, `InstitutionSubjects`.`institution_id`, `InstitutionSubjects`.`academic_period_id`, `InstitutionSubjects`.`education_subject_id`, `InstitutionSubjectStudents`.`modified_user_id`, `InstitutionSubjectStudents`.`modified`, `InstitutionSubjectStudents`.`created_user_id`, `InstitutionSubjectStudents`.`created`
FROM `z_2759_institution_subject_students` AS `InstitutionSubjectStudents`
INNER JOIN `institution_subjects` AS `InstitutionSubjects`
ON `InstitutionSubjects`.`id` = `InstitutionSubjectStudents`.`institution_subject_id`
GROUP BY 
  `InstitutionSubjectStudents`.`student_id`, 
  `InstitutionSubjectStudents`.`institution_class_id`, 
  `InstitutionSubjects`.`institution_id`,
  `InstitutionSubjects`.`academic_period_id`, 
  `InstitutionSubjects`.`education_subject_id`
;

-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Results.index', `_edit` = 'Results.edit' WHERE `id` = 1015;

-- patch grading types - result_type, pass_mark and max
UPDATE assessment_grading_types
JOIN (
  SELECT assessment_grading_type_id, pass_mark, max, result_type
  FROM z_2759_assessment_items
  GROUP BY assessment_grading_type_id
) a ON a.assessment_grading_type_id = assessment_grading_types.id
SET assessment_grading_types.pass_mark = a.pass_mark,
assessment_grading_types.max = a.max,
assessment_grading_types.result_type = a.result_type;

-- patch assessments

DROP PROCEDURE IF EXISTS patchAssessments;
DELIMITER $$

CREATE PROCEDURE patchAssessments()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE assessmentId, newAssessmentId, newAssessmentPeriodId, periodId, statusId, currentPeriodId INT(11);
  DECLARE assessmentName VARCHAR(100);
  DECLARE periodStart, periodEnd, enabledDate, disabledDate date;
  
  DECLARE cursor_assessments CURSOR FOR
      SELECT 
      assessments.id, assessments.name, ap.id, astat.id, 
      ap.start_date, ap.end_date, astat.date_enabled, astat.date_disabled
      FROM assessments
      LEFT JOIN z_2759_assessment_statuses astat ON astat.assessment_id = assessments.id
      LEFT JOIN z_2759_assessment_status_periods asp ON asp.assessment_status_id = astat.id
      LEFT JOIN academic_periods ap ON ap.id = asp.academic_period_id
      GROUP BY assessments.id, ap.id
      ORDER BY ap.start_date DESC
      ;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN cursor_assessments;

  UPDATE assessments SET academic_period_id = 0;
  UPDATE assessment_item_results SET assessment_period_id = 0;
  TRUNCATE TABLE assessment_periods;

  read_loop: LOOP
    FETCH cursor_assessments INTO assessmentId, assessmentName, periodId, statusId, periodStart, periodEnd, enabledDate, disabledDate;
    IF done THEN
      LEAVE read_loop;
    END IF;

    IF periodId IS NULL THEN
      UPDATE assessments SET academic_period_id = (
        select id from academic_periods order by start_date desc limit 1
      )
      WHERE assessments.id = assessmentId;
    ELSE
      SELECT academic_period_id INTO currentPeriodId
      FROM assessments
      WHERE assessments.id = assessmentId;

      IF currentPeriodId = 0 THEN
        UPDATE assessments SET academic_period_id = periodId
        WHERE assessments.id = assessmentId;

        INSERT INTO assessment_periods
        SELECT
        NULL,
        '',
        assessmentName,
        periodStart,
        periodEnd,
        enabledDate,
        disabledDate,
        20,
        assessmentId,
        null, null, 1, now()
        FROM z_2759_assessment_statuses
        WHERE z_2759_assessment_statuses.id = statusId;

        SELECT LAST_INSERT_ID() INTO newAssessmentPeriodId;

        UPDATE assessment_item_results
        SET assessment_period_id = newAssessmentPeriodId
        WHERE academic_period_id = periodId
        AND assessment_id = assessmentId;
      ELSE
        INSERT INTO assessments
        SELECT
        NULL,
        code,
        name,
        description,
        type,
        periodId,
        education_grade_id,
        modified_user_id,
        modified,
        -1,
        created
        FROM assessments
        WHERE assessments.id = assessmentId;

        SELECT LAST_INSERT_ID() INTO newAssessmentId;

        INSERT INTO assessment_periods
        SELECT
        NULL,
        '',
        assessmentName,
        periodStart,
        periodEnd,
        enabledDate,
        disabledDate,
        20,
        newAssessmentId,
        assessmentId, null, -1, now()
        FROM z_2759_assessment_statuses
        WHERE z_2759_assessment_statuses.id = statusId;

        SELECT LAST_INSERT_ID() INTO newAssessmentPeriodId;

        UPDATE assessment_item_results
        SET assessment_period_id = newAssessmentPeriodId,
        assessment_id = newAssessmentId
        WHERE academic_period_id = periodId AND assessment_id = assessmentId;
      END IF;

    END IF;

    UPDATE assessments SET created_user_id = 1 WHERE created_user_id = -1;
    UPDATE assessment_periods SET created_user_id = 1 WHERE created_user_id = -1;
    UPDATE assessment_periods SET modified_user_id = null WHERE modified is NULL;

  END LOOP read_loop;

  CLOSE cursor_assessments;
END
$$

DELIMITER ;

CALL patchAssessments;
DROP PROCEDURE IF EXISTS patchAssessments;


-- 3.5.2
UPDATE config_items SET value = '3.5.2' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;

