-- POCOR-2733
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2733', NOW());

-- Backup table
CREATE TABLE `z_2733_staff_leaves` LIKE  `staff_leaves`;
INSERT INTO `z_2733_staff_leaves` SELECT * FROM `staff_leaves` WHERE 1;

CREATE TABLE `z_2733_institution_surveys` LIKE  `institution_surveys`;
INSERT INTO `z_2733_institution_surveys` SELECT * FROM `institution_surveys` WHERE 1;

CREATE TABLE `z_2733_workflow_records` LIKE  `workflow_records`;
INSERT INTO `z_2733_workflow_records` SELECT * FROM `workflow_records` WHERE 1;

-- institution_student_surveys
ALTER TABLE `institution_student_surveys` ADD `parent_form_id` int(11) NOT NULL COMMENT 'links to institution_surveys.survey_form_id' AFTER `survey_form_id`;

-- patch Staff Leaves
DROP PROCEDURE IF EXISTS patchStaffLeaves;
DELIMITER $$

CREATE PROCEDURE patchStaffLeaves()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE workflowId, workflowModelId, openStepId INT(11);
  DECLARE leaves CURSOR FOR
    SELECT `WorkflowsFilters`.`workflow_id`, `WorkflowModels`.`id`
    FROM `workflows_filters` AS `WorkflowsFilters`
    INNER JOIN `workflows` AS `Workflows` ON `Workflows`.`id` = `WorkflowsFilters`.`workflow_id`
    INNER JOIN `workflow_models` AS `WorkflowModels` ON `WorkflowModels`.`id` = `Workflows`.`workflow_model_id`
    WHERE `WorkflowsFilters`.`filter_id` <> 0
    AND `WorkflowModels`.`model` = 'Staff.Leaves'
    GROUP BY `WorkflowsFilters`.`workflow_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN leaves;

  read_loop: LOOP
    FETCH leaves INTO workflowId, workflowModelId;
    IF done THEN
      LEAVE read_loop;
    END IF;

    SELECT `id` INTO openStepId FROM `workflow_steps` WHERE `workflow_id` = workflowId AND `stage` = 0;
    UPDATE `staff_leaves` SET `status_id` = openStepId WHERE `staff_leave_type_id` IN (SELECT `filter_id` FROM `workflows_filters` WHERE `workflow_id` = workflowId);
  UPDATE `workflow_records` SET `workflow_step_id` = openStepId WHERE `workflow_model_id` = workflowModelId AND `model_reference` IN (
    SELECT `id` FROM `staff_leaves` WHERE `staff_leave_type_id` IN (SELECT `filter_id` FROM `workflows_filters` WHERE `workflow_id` = workflowId)
  );

  END LOOP read_loop;

  CLOSE leaves;
END
$$

DELIMITER ;

CALL patchStaffLeaves;

DROP PROCEDURE IF EXISTS patchStaffLeaves;

-- patch Institution Surveys
DROP PROCEDURE IF EXISTS patchInstitutionSurveys;
DELIMITER $$

CREATE PROCEDURE patchInstitutionSurveys()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE workflowId, workflowModelId, openStepId INT(11);
  DECLARE surveys CURSOR FOR
    SELECT `WorkflowsFilters`.`workflow_id`, `WorkflowModels`.`id`
    FROM `workflows_filters` AS `WorkflowsFilters`
    INNER JOIN `workflows` AS `Workflows` ON `Workflows`.`id` = `WorkflowsFilters`.`workflow_id`
    INNER JOIN `workflow_models` AS `WorkflowModels` ON `WorkflowModels`.`id` = `Workflows`.`workflow_model_id`
    WHERE `WorkflowsFilters`.`filter_id` <> 0
    AND `WorkflowModels`.`model` = 'Institution.InstitutionSurveys'
    GROUP BY `WorkflowsFilters`.`workflow_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN surveys;

  read_loop: LOOP
    FETCH surveys INTO workflowId, workflowModelId;
    IF done THEN
      LEAVE read_loop;
    END IF;

    SELECT `id` INTO openStepId FROM `workflow_steps` WHERE `workflow_id` = workflowId AND `stage` = 0;
    UPDATE `institution_surveys` SET `status_id` = openStepId WHERE `survey_form_id` IN (SELECT `filter_id` FROM `workflows_filters` WHERE `workflow_id` = workflowId);
        UPDATE `workflow_records` SET `workflow_step_id` = openStepId WHERE `workflow_model_id` = workflowModelId AND `model_reference` IN (
                SELECT `id` FROM `institution_surveys` WHERE `survey_form_id` IN (SELECT `filter_id` FROM `workflows_filters` WHERE `workflow_id` = workflowId)
        );

  END LOOP read_loop;

  CLOSE surveys;
END
$$

DELIMITER ;

CALL patchInstitutionSurveys;

DROP PROCEDURE IF EXISTS patchInstitutionSurveys;

-- patch Institution Student Surveys
DROP PROCEDURE IF EXISTS patchStudentSurveys;
DELIMITER $$

CREATE PROCEDURE patchStudentSurveys()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE surveyFormId, questionId, parentFormId INT(11);
  DECLARE surveys CURSOR FOR
    SELECT `InstitutionStudentSurveys`.`survey_form_id`
    FROM `institution_student_surveys` AS `InstitutionStudentSurveys`
    GROUP BY `InstitutionStudentSurveys`.`survey_form_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN surveys;

  read_loop: LOOP
    FETCH surveys INTO surveyFormId;
    IF done THEN
      LEAVE read_loop;
    END IF;

    SELECT MAX(`id`) INTO questionId FROM `survey_questions` WHERE `params` = CONCAT('{"survey_form_id":"', surveyFormId, '"}');
    SELECT MAX(`survey_form_id`) INTO parentFormId FROM `survey_forms_questions` WHERE `survey_question_id` = questionId;
    UPDATE `institution_student_surveys` SET `parent_form_id` = parentFormId WHERE `survey_form_id` = surveyFormId;

  END LOOP read_loop;

  CLOSE surveys;
END
$$

DELIMITER ;

CALL patchStudentSurveys;

DROP PROCEDURE IF EXISTS patchStudentSurveys;

UPDATE `institution_student_surveys` AS `InstitutionStudentSurveys`
INNER JOIN `institution_surveys` AS `InstitutionSurveys`
ON `InstitutionSurveys`.`institution_id` = `InstitutionStudentSurveys`.`institution_id`
AND `InstitutionSurveys`.`academic_period_id` = `InstitutionStudentSurveys`.`academic_period_id`
AND `InstitutionSurveys`.`survey_form_id` = `InstitutionStudentSurveys`.`parent_form_id`
SET `InstitutionStudentSurveys`.`status_id` = `InstitutionSurveys`.`status_id`;


-- 
-- POCOR-1694
-- 

-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-1694', NOW());

-- patch institution_subjects, recreate table to rebuild index

ALTER TABLE `institution_classes` RENAME `z_1694_institution_classes`;

DROP TABLE IF EXISTS `institution_subjects`;
CREATE TABLE IF NOT EXISTS `institution_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `no_of_seats` int(3) DEFAULT NULL,
  `institution_id` int(11) NOT NULL,
  `education_subject_id` int(11) DEFAULT NULL,
  `academic_period_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_subjects`
  ADD KEY `academic_period_id` (`academic_period_id`),
  ADD KEY `institution_id` (`institution_id`);

INSERT INTO `institution_subjects` SELECT * FROM `z_1694_institution_classes`;

-- end institution_subjects


-- patch institution_subject_staff, recreate table to rebuild index

ALTER TABLE `institution_class_staff` RENAME `z_1694_institution_class_staff`;

DROP TABLE IF EXISTS `institution_subject_staff`;
CREATE TABLE `institution_subject_staff` (
  `id` CHAR(36) NOT NULL,
  `status` int(1) NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_subject_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_subject_staff`
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `institution_subject_id` (`institution_subject_id`);

INSERT INTO `institution_subject_staff` SELECT * FROM `z_1694_institution_class_staff`;
UPDATE institution_subject_staff SET `id` = uuid();

-- end institution_subject_staff


-- patch institution_subject_students, recreate table to rebuild index

ALTER TABLE `institution_class_students` RENAME `z_1694_institution_class_students`;

DROP TABLE IF EXISTS `institution_subject_students`;
CREATE TABLE IF NOT EXISTS `institution_subject_students` (
  `id` CHAR(36) NOT NULL,
  `status` int(1) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_subject_id` int(11) NOT NULL,
  `institution_class_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_subject_students`
  ADD KEY `student_id` (`student_id`),
  ADD KEY `institution_subject_id` (`institution_subject_id`),
  ADD KEY `institution_class_id` (`institution_class_id`);

INSERT INTO `institution_subject_students` SELECT * FROM `z_1694_institution_class_students`;
DELETE FROM `institution_subject_students` WHERE NOT EXISTS (
	SELECT 1 FROM `security_users` WHERE `security_users`.`id` = `institution_subject_students`.`student_id`
);
UPDATE institution_subject_students SET `id` = uuid();

-- end institution_subject_students

-- patch institution_class_subjects, recreate table to rebuild index

ALTER TABLE `institution_section_classes` RENAME `z_1694_institution_section_classes`;

DROP TABLE IF EXISTS `institution_class_subjects`;
CREATE TABLE `institution_class_subjects` (
  `id` CHAR(36) NOT NULL,
  `status` int(1) NOT NULL,
  `institution_class_id` int(11) NOT NULL,
  `institution_subject_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_class_subjects`
  ADD KEY `institution_class_id` (`institution_class_id`),
  ADD KEY `institution_subject_id` (`institution_subject_id`);

INSERT INTO `institution_class_subjects` SELECT * FROM `z_1694_institution_section_classes`;
UPDATE institution_class_subjects SET `id` = uuid();

-- end institution_class_subjects


-- patch institution_classes, recreate table to rebuild index

ALTER TABLE `institution_sections` RENAME `z_1694_institution_sections`;

DROP TABLE IF EXISTS `institution_classes`;
CREATE TABLE `institution_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `class_number` int(11) DEFAULT NULL COMMENT 'This column is being used to determine whether this class is a multi-grade or single-grade.',
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_shift_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_classes`
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `academic_period_id` (`academic_period_id`),
  ADD KEY `institution_shift_id` (`institution_shift_id`),
  ADD KEY `institution_id` (`institution_id`);

INSERT INTO `institution_classes` SELECT * FROM `z_1694_institution_sections`;

-- end institution_classes


-- patch institution_class_grades, recreate table to rebuild index

ALTER TABLE `institution_section_grades` RENAME `z_1694_institution_section_grades`;

DROP TABLE IF EXISTS `institution_class_grades`;
CREATE TABLE `institution_class_grades` (
  `id` CHAR(36) NOT NULL,
  `institution_class_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_class_grades`
  ADD KEY `institution_class_id` (`institution_class_id`),
  ADD KEY `education_grade_id` (`education_grade_id`);

INSERT INTO `institution_class_grades`
SELECT
uuid(),
institution_section_id,
education_grade_id,
modified_user_id,
modified,
created_user_id,
created
FROM `z_1694_institution_section_grades`;

-- end institution_class_grades


-- patch institution_class_students, recreate table to rebuild index

ALTER TABLE `institution_section_students` RENAME `z_1694_institution_section_students`;

DROP TABLE IF EXISTS `institution_class_students`;
CREATE TABLE `institution_class_students` (
  `id` char(36) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_class_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `student_status_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_class_students`
  ADD KEY `student_id` (`student_id`),
  ADD KEY `institution_class_id` (`institution_class_id`),
  ADD KEY `education_grade_id` (`education_grade_id`),
  ADD KEY `student_status_id` (`student_status_id`);

INSERT INTO `institution_class_students` SELECT * FROM `z_1694_institution_section_students`;

-- end institution_class_students


-- patch institution_quality_rubrics, recreate table to rebuild index
ALTER TABLE `institution_quality_rubrics` RENAME `z_1694_institution_quality_rubrics`;

DROP TABLE IF EXISTS `institution_quality_rubrics`;
CREATE TABLE `institution_quality_rubrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '-1 -> Expired, 0 -> New, 1 -> Draft, 2 -> Completed',
  `comment` text,
  `rubric_template_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `institution_class_id` int(11) NOT NULL,
  `institution_subject_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_quality_rubrics`
  ADD KEY `rubric_template_id` (`rubric_template_id`),
  ADD KEY `academic_period_id` (`academic_period_id`),
  ADD KEY `education_grade_id` (`education_grade_id`),
  ADD KEY `institution_class_id` (`institution_class_id`),
  ADD KEY `institution_subject_id` (`institution_subject_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `institution_id` (`institution_id`);

INSERT INTO `institution_quality_rubrics` SELECT * FROM `z_1694_institution_quality_rubrics`;

-- end institution_quality_rubrics


-- patch institution_quality_visits, recreate table to rebuild index
ALTER TABLE `institution_quality_visits` RENAME `z_1694_institution_quality_visits`;

DROP TABLE IF EXISTS `institution_quality_visits`;
CREATE TABLE `institution_quality_visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `comment` text,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob,
  `quality_visit_type_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `institution_subject_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_quality_visits`
  ADD KEY `quality_visit_type_id` (`quality_visit_type_id`),
  ADD KEY `academic_period_id` (`academic_period_id`),
  ADD KEY `institution_subject_id` (`institution_subject_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `institution_id` (`institution_id`);

INSERT INTO `institution_quality_visits` SELECT * FROM `z_1694_institution_quality_visits`;
-- end institution_quality_visits


UPDATE `labels` SET `field`='subjects' WHERE `module`='InstitutionSections' AND `field`='classes';
UPDATE `labels` SET `field`='institution_subject_id'
WHERE 
 	`module` IN (
 		'Absences',
 		'StaffClasses',
 		'StaffAbsences',
 		'StudentClasses',
 		'InstitutionRubrics',
 		'InstitutionQualityVisits',
 		'InstitutionStudentAbsences'
 	) AND `field`='institution_class_id';
UPDATE `labels` SET `field`='institution_class_id'
WHERE 
 	`module` IN (
		'Absences',
		'StaffAbsences',
		'StudentClasses',
		'StudentSections',
		'InstitutionRubrics',
		'InstitutionStudentAbsences'
 	) AND `field`='institution_section_id';
UPDATE `labels` SET `field`='class'
WHERE 
 	`module` IN (
		'InstitutionStudentAbsences',
		'StudentBehaviours',
		'Students'
	) AND `field`='section';
UPDATE `labels` SET `field`='institution_classes_code' WHERE `module`='Imports' AND `field`='institution_sections_code';
UPDATE `labels` SET `field`='InstitutionClasses' WHERE `module`='Imports' AND `field`='InstitutionSections';
UPDATE `labels` SET `field`='number_of_classes' WHERE `module`='InstitutionSections' AND `field`='number_of_sections';
UPDATE `labels` SET `field`='institution_class' WHERE `module`='StaffClasses' AND `field`='institution_section';
UPDATE `labels` SET `field`='select_class' WHERE `module`='Absences' AND `field`='select_section';
UPDATE `labels` SET `module`='InstitutionSubjects' WHERE `module`='InstitutionClasses';
UPDATE `labels` SET `module`='StaffSubjects' WHERE `module`='StaffClasses';
UPDATE `labels` SET `module`='StudentSubjects' WHERE `module`='StudentClasses';
UPDATE `labels` SET `module`='InstitutionClasses' WHERE `module`='InstitutionSections';
UPDATE `labels` SET `module`='StudentClasses' WHERE `module`='StudentSections';

UPDATE `import_mapping` SET `lookup_model`='InstitutionClasses' WHERE `id`=66;

UPDATE `security_functions` SET `_view`='AllClasses.index|AllClasses.view|Classes.index|Classes.view', `_edit`='AllClasses.edit|Classes.edit', `_add`='Classes.add', `_delete`='Classes.remove', `_execute`=NULL WHERE `id`='1007';
UPDATE `security_functions` SET `_view`='Classes.index|Classes.view', `_edit`='Classes.edit', `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='1008';
UPDATE `security_functions` SET `_view`='AllSubjects.index|AllSubjects.view|Subjects.index|Subjects.view', `_edit`='AllSubjects.edit|Subjects.edit', `_add`='Subjects.add', `_delete`='Subjects.remove', `_execute`=NULL WHERE `id`='1009';
UPDATE `security_functions` SET `_view`='Subjects.index|Subjects.view', `_edit`='Subjects.edit', `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='1010';
UPDATE `security_functions` SET `_view`='Classes.index|Classes.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='2012';
UPDATE `security_functions` SET `_view`='Subjects.index|Subjects.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='2013';
UPDATE `security_functions` SET `_view`='Classes.index|Classes.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='3013';
UPDATE `security_functions` SET `_view`='Subjects.index|Subjects.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='3014';
UPDATE `security_functions` SET `_view`='StudentClasses.index', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='7011';
UPDATE `security_functions` SET `_view`='StaffClasses.index', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='7022';
UPDATE `security_functions` SET `_view`='StaffSubjects.index', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='7023';

-- 
-- END POCOR-1694
-- 


-- POCOR-2675
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2675', NOW());

ALTER TABLE `institution_positions` ADD `is_homeroom` INT(1) NOT NULL DEFAULT '1' AFTER `institution_id`;

ALTER TABLE `security_roles` ADD `code` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `name`;
ALTER TABLE `security_roles` ADD INDEX(`code`);

-- updating code for preset roles
UPDATE security_roles SET code = 'PRINCIPAL' WHERE name IN ('School Principal', 'Principal');

UPDATE security_roles SET code = 'ADMINISTRATOR' WHERE name = 'Administrator';
UPDATE security_roles SET code = 'GROUP_ADMINISTRATOR' WHERE name = 'Group Administrator';
UPDATE security_roles SET code = 'TEACHER' WHERE name = 'Teacher';
UPDATE security_roles SET code = 'STAFF' WHERE name = 'Staff';
UPDATE security_roles SET code = 'STUDENT' WHERE name = 'Student';
UPDATE security_roles SET code = 'GUARDIAN' WHERE name = 'Guardian';

-- insert if not exists
SELECT (MAX(`order`)+1) into @highestOrder from security_roles;
INSERT INTO `security_roles` (`name`, `code`, `order`, `visible`, `security_group_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT 'Homeroom Teacher', 'HOMEROOM_TEACHER', @highestOrder, 1, -1, NULL, NULL, 1, '0000-00-00 00:00:00' FROM dual WHERE NOT EXISTS (SELECT 1 FROM security_roles WHERE name = 'Homeroom Teacher');

UPDATE security_roles SET code = 'HOMEROOM_TEACHER' WHERE name = 'Homeroom Teacher';

INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
values (uuid(), 'InstitutionPositions', 'is_homeroom', 'Institutions -> Positions', 'Homeroom Teacher', 1, 1, NOW());

DROP TABLE IF EXISTS `z1968_staff_qualifications`;
DROP TABLE IF EXISTS `z2423_assessment_items`;
DROP TABLE IF EXISTS `z2423_assessment_item_results`;
DROP TABLE IF EXISTS `z2515_institution_shifts`;
DROP TABLE IF EXISTS `z_2392_institution_infrastructures`;
DROP TABLE IF EXISTS `z_2463_institution_section_students`;
DROP TABLE IF EXISTS `z_2500_institution_section_students`;
DROP TABLE IF EXISTS `z_2500_security_groups`;
DROP TABLE IF EXISTS `z_2501_institution_section_students`;
DROP TABLE IF EXISTS `z_2506_institution_positions`;
DROP TABLE IF EXISTS `z_2526_authentication_type_attributes`;
DROP TABLE IF EXISTS `z_2526_config_items`;
DROP TABLE IF EXISTS `z_2535_institution_positions`;


-- POCOR-2749
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2749', NOW());

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Absence - Excused', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Absence - Excused');

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Absence - Unexcused', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Absence - Unexcused');

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Late', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Late');


-- 3.4.18
UPDATE config_items SET value = '3.4.18' WHERE code = 'db_version';
