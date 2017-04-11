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



