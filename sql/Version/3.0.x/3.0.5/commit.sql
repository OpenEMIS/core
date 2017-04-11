-- PHPOE-872
-- 22nd July 2015

--
-- For Workflow (Administration)
--

RENAME TABLE `workflow_step_roles` TO `workflow_steps_roles`;
RENAME TABLE `workflow_submodels` TO `workflows_filters`;

ALTER TABLE `workflow_models` CHANGE `submodel` `filter` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `workflows_filters` CHANGE `submodel_reference` `filter_id` INT(11) NOT NULL;

--
-- For Student Transfer
--

-- 23rd July 2015

-- security_functions
INSERT INTO `security_functions`
(`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1019, 'Transfer Request', 'Institutions', 'Institutions', 'Students', 1000, NULL, NULL, NULL, NULL, 'Transfers.add', 1019, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1020, 'Transfer Approval', 'Dashboard', 'Institutions', 'Students', 1000, NULL, NULL, NULL, NULL, 'Transfers.edit', 1020, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- New table - student_statuses
DROP TABLE IF EXISTS `student_statuses`;
CREATE TABLE IF NOT EXISTS `student_statuses` (
  `id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `student_statuses`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `student_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

TRUNCATE TABLE `student_statuses`;
INSERT INTO `student_statuses` (`id`, `code`, `name`) VALUES
(1, 'CURRENT', 'Current'),
(2, 'PENDING_TRANSFER', 'Pending Transfer'),
(3, 'TRANSFERRED', 'Transferred'),
(4, 'DROPOUT', 'Dropout'),
(5, 'EXPELLED', 'Expelled'),
(6, 'GRADUATED', 'Graduated');

-- New table - institution_student_transfers
DROP TABLE IF EXISTS `institution_student_transfers`;
CREATE TABLE IF NOT EXISTS `institution_student_transfers` (
  `id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Approve, 2 -> Reject',
  `institution_id` int(11) NOT NULL,
  `education_programme_id` int(11) NOT NULL,
  `previous_institution_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_student_transfers`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `institution_student_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- patch institution_site_students
DELIMITER $$

DROP PROCEDURE IF EXISTS student_transfer
$$
CREATE PROCEDURE student_transfer()
BEGIN
	DECLARE done INT DEFAULT FALSE;
	DECLARE statusId, newStatusId INT(3);
	DECLARE fov CURSOR FOR 
		SELECT `FieldOptionValues`.`id`, `StudentStatuses`.`id` AS `newId`
		FROM `field_option_values` AS `FieldOptionValues`
		INNER JOIN `field_options` AS `FieldOptions` ON `FieldOptions`.`id` = `FieldOptionValues`.`field_option_id`
		INNER JOIN `student_statuses` AS `StudentStatuses` ON `StudentStatuses`.`name` = `FieldOptionValues`.`name`
		WHERE `FieldOptions`.`code` = 'StudentStatuses';
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

	OPEN fov;

	read_loop: LOOP
	FETCH fov INTO statusId, newStatusId;
	IF done THEN
		LEAVE read_loop;
	END IF;

		UPDATE 	`institution_site_students` AS `Students`
		SET 	`Students`.`student_status_id` = newStatusId
		WHERE	`Students`.`student_status_id` = statusId;

	END LOOP read_loop;

	CLOSE fov;
END
$$

CALL student_transfer
$$

DROP PROCEDURE IF EXISTS student_transfer
$$

DELIMITER ;


-- PHPOE-1694
UPDATE `labels` SET `en` = 'Class Name' WHERE `labels`.`module` = 'InstitutionSiteSections' AND `labels`.`field` = 'name';
UPDATE `labels` SET `en` = 'Name' WHERE `labels`.`module` = 'InstitutionSiteClasses' AND `labels`.`field` = 'name';
UPDATE `labels` SET `en` = 'Subject' WHERE `labels`.`module` = 'Absences' AND `labels`.`field` = 'institution_site_class_id';
UPDATE `labels` SET `en` = 'Class' WHERE `labels`.`module` = 'Absences' AND `labels`.`field` = 'institution_site_section_id';
UPDATE `labels` SET `en` = 'Select Class' WHERE `labels`.`module` = 'Absences' AND `labels`.`field` = 'select_section';

-- Fix translations
UPDATE `labels` SET `en` = 'العربية' WHERE `labels`.`module` = 'Translations' AND `labels`.`field` = 'ar';
UPDATE `labels` SET `en` = 'español' WHERE `labels`.`module` = 'Translations' AND `labels`.`field` = 'es';
UPDATE `labels` SET `en` = 'Français' WHERE `labels`.`module` = 'Translations' AND `labels`.`field` = 'fr';
UPDATE `labels` SET `en` = 'русский' WHERE `labels`.`module` = 'Translations' AND `labels`.`field` = 'ru';
UPDATE `labels` SET `en` = '中文' WHERE `labels`.`module` = 'Translations' AND `labels`.`field` = 'zh';

INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('InstitutionSiteSections', 'classes', NULL, 'Subjects', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('InstitutionSiteSections', 'number_of_sections', NULL, 'Number Of Classes', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('Students', 'section', NULL, 'Class', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StudentSections', 'institution_site_section_id', NULL, 'Class', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StudentClasses', 'institution_site_section_id', NULL, 'Class', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StudentClasses', 'institution_site_class_id', NULL, 'Name', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StaffClasses', 'institution_site_section', NULL, 'Class', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StaffClasses', 'institution_site_class_id', NULL, 'Name', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StudentBehaviours', 'section', NULL, 'Class', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('InstitutionSiteStudentAbsences', 'section', NULL, 'Class', '1', NOW());

UPDATE `security_functions` SET `name` = 'Classes' WHERE `id` IN (1006, 2012, 3013);
UPDATE `security_functions` SET `name` = 'Subjects' WHERE `id` IN (1007, 2013, 3014);

-- PHPOE-1713
DELETE FROM `security_functions` WHERE `controller` = 'Census' AND `category` = 'Totals';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Structure';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Grade - Subjects';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Certifications';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Field of Study';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Programme Orientations';
DELETE FROM `security_functions` WHERE `controller` IN ('InfrastructureLevels', 'InfrastructureTypes', 'InfrastructureCustomFields');
DELETE FROM `security_functions` WHERE `controller` = 'Dashboards' AND `name` = 'Dashboards';
DELETE FROM `security_functions` WHERE `controller` = 'Security' AND `name` = 'List of Groups';

UPDATE `security_functions` SET `name` = 'Setup' WHERE `controller` = 'Education' AND `name` = 'Education Subjects';
UPDATE `security_functions` SET `name` = 'Assessments' WHERE `controller` = 'Assessments' AND `name` = 'Items';
UPDATE `security_functions` SET `controller` = 'FieldOptions' WHERE `controller` = 'FieldOption' AND `name` = 'Setup';
UPDATE `security_functions` SET `controller` = 'Configurations' WHERE `controller` = 'Config' AND `name` = 'Configurations';
UPDATE `security_functions` SET `controller` = 'Securities', `category` = 'Security' WHERE `controller` = 'Security';
UPDATE `security_functions` SET `name` = 'User Roles' WHERE `controller` = 'Securities' AND `name` = 'Roles';
UPDATE `security_functions` SET `controller` = 'Surveys', `category` = 'Survey' WHERE `module` = 'Administration' AND `category` = 'Surveys';
UPDATE `security_functions` SET `name` = 'Forms' WHERE `controller` = 'Surveys' AND `module` = 'Administration' AND `name` = 'Templates';
UPDATE `security_functions` SET `controller` = 'Alerts' WHERE `controller` = 'Sms' AND `module` = 'Administration' AND `category` = 'Communications';
UPDATE `security_functions` SET `name` = 'Setup', `controller` = 'Rubrics', `category` = 'Rubrics' WHERE `controller` = 'QualityRubrics' AND `module` = 'Administration' AND `category` = 'Quality' AND `name` = 'Rubrics';
UPDATE `security_functions` SET `controller` = 'Rubrics', `category` = 'Rubrics' WHERE `controller` = 'QualityStatuses' AND `module` = 'Administration' AND `category` = 'Quality' AND `name` = 'Status';
UPDATE `security_functions` SET `name` = 'Steps', `controller` = 'Workflows' WHERE `controller` = 'WorkflowSteps' AND `module` = 'Administration' AND `name` = 'WorkflowSteps';

SET @funcId := 0;

SET @id := 1021;

-- Survey New
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'New' AND `category` = 'Surveys';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'New', 'Institutions', 'Institutions', 'Surveys', 1000, 
'Surveys.index', 
NULL, 
NULL, 
'Surveys.edit|Surveys.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Survey Completed
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Completed' AND `category` = 'Surveys';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Completed', 'Institutions', 'Institutions', 'Surveys', 1000, 
'Surveys.index', 
NULL, 
NULL, 
'Surveys.view|Surveys.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Quality Rubrics
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Rubrics' AND `category` = 'Quality';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Rubrics', 'Institutions', 'Institutions', 'Quality', 1000, 
'Rubrics.index|Rubrics.view', 
'Rubrics.edit', 
NULL, 
'Rubrics.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Quality Visits
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Visits' AND `category` = 'Quality';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Visits', 'Institutions', 'Institutions', 'Quality', 1000, 
'Visits.index|Visits.view', 
'Visits.edit', 
'Visits.add', 
'Visits.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

SET @id := 3024;
-- Staff Training Needs
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Needs' AND `category` = 'Training';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Needs', 'Staff', 'Staff', 'Training', 3000, 
'TrainingNeeds.index|TrainingNeeds.view', 
'TrainingNeeds.edit', 
'TrainingNeeds.add', 
'TrainingNeeds.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Staff Training Results
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Results' AND `category` = 'Training';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Results', 'Staff', 'Staff', 'Training', 3000, 
'TrainingResults.index|TrainingResults.view', 
NULL, 
NULL, 
NULL, NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Staff Achievements
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Achievements' AND `category` = 'Training';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Achievements', 'Staff', 'Staff', 'Training', 3000, 
'Achievements.index|Achievements.view', 
'Achievements.edit', 
'Achievements.add', 
'Achievements.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

SET @id := 5000;

-- Administration Module

-- Area Levels
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Areas' AND `name` = 'Area Levels';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Area Levels', 'Areas', 'Administration', 'Administrative Boundaries', 5000, 
'Levels.index|Levels.view|AdministrativeLevels.index|AdministrativeLevels.view', 
'Levels.edit|AdministrativeLevels.edit', 
'Levels.add|AdministrativeLevels.add', 
'Levels.remove|AdministrativeLevels.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Areas
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Areas' AND `name` = 'Areas';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Areas', 'Areas', 'Administration', 'Administrative Boundaries', 5000, 
'Areas.index|Areas.view|Administratives.index|Administratives.view', 
'Areas.edit|Administratives.edit', 
'Areas.add|Administratives.add', 
'Areas.remove|Administratives.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Period Levels
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'AcademicPeriods' AND `name` = 'Academic Period Levels';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Academic Period Levels', 'AcademicPeriods', 'Administration', 'Academic Periods', 5000, 
'Levels.index|Levels.view', 
'Levels.edit', 
'Levels.add', 
'Levels.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Periods
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'AcademicPeriods' AND `name` = 'Academic Periods';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Academic Periods', 'AcademicPeriods', 'Administration', 'Academic Periods', 5000, 
'Periods.index|Periods.view', 
'Periods.edit', 
'Periods.add', 
'Periods.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Education System
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Systems';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Education Systems', 'Education', 'Administration', 'Education', 5000, 
'Systems.index|Systems.view', 
'Systems.edit', 
'Systems.add', 
'Systems.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Education Level
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Levels';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Education Levels', 'Education', 'Administration', 'Education', 5000, 
'Levels.index|Levels.view', 
'Levels.edit', 
'Levels.add', 
'Levels.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Education Cycle
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Cycles';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Education Cycles', 'Education', 'Administration', 'Education', 5000, 
'Cycles.index|Cycles.view', 
'Cycles.edit', 
'Cycles.add', 
'Cycles.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Education Programme
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Programmes';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Education Programmes', 'Education', 'Administration', 'Education', 5000, 
'Programmes.index|Programmes.view', 
'Programmes.edit', 
'Programmes.add', 
'Programmes.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Education Grade
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Grades';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Education Grades', 'Education', 'Administration', 'Education', 5000, 
'Grades.index|Grades.view', 
'Grades.edit', 
'Grades.add', 
'Grades.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Education Setup
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Setup';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Setup', 'Education', 'Administration', 'Education', 5000, 
'Subjects.index|Subjects.view|Certifications.index|Certifications.view|FieldOfStudies.index|FieldOfStudies.view|ProgrammeOrientations.index|ProgrammeOrientations.view', 
'Subjects.edit|Certifications.edit|FieldOfStudies.edit|ProgrammeOrientations.edit', 
'Subjects.add|Certifications.add|FieldOfStudies.add|ProgrammeOrientations.add', 
'Subjects.remove|Certifications.remove|FieldOfStudies.remove|ProgrammeOrientations.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Assessments
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Assessments' AND `name` = 'Assessments';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Assessments', 'Assessments', 'Administration', 'Assessments', 5000, 
'Assessments.index|Assessments.view', 
'Assessments.edit', 
'Assessments.add', 
'Assessments.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Assessment Grading Types
DELETE FROM `security_functions` WHERE `id` = @id;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Grading Types', 'Assessments', 'Administration', 'Assessments', 5000, 
'GradingTypes.index|GradingTypes.view|GradingOptions.index|GradingOptions.view', 
'GradingTypes.edit|GradingOptions.edit', 
'GradingTypes.add|GradingOptions.add', 
'GradingTypes.remove|GradingOptions.remove', @id, 1, 1, NOW());
SET @id := @id + 1;
-- end

-- Assessment Status
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Assessments' AND `name` = 'Status';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Status', 'Assessments', 'Administration', 'Assessments', 5000, 
'Status.index|Status.view', 
'Status.edit', 
'Status.add', 
'Status.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Field Options
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'FieldOptions' AND `name` = 'Setup';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Setup', 'FieldOptions', 'Administration', 'Field Options', 5000, 
'index|view', 
'edit', 
'add', 
'remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Custom Fields - General
DELETE FROM `security_functions` WHERE `id` = @id;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'General', 'CustomFields', 'Administration', 'Custom Fields', 5000, 
'Fields.index|Fields.view|Pages.index|Pages.view', 
'Fields.edit|Pages.edit', 
'Fields.add|Pages.add', 
'Fields.remove|Pages.remove', @id, 1, 1, NOW());
SET @id := @id + 1;
-- end

-- Custom Fields - Institution
DELETE FROM `security_functions` WHERE `id` = @id;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Institution', 'InstitutionCustomFields', 'Administration', 'Custom Fields', 5000, 
'Fields.index|Fields.view|Pages.index|Pages.view', 
'Fields.edit|Pages.edit', 
'Fields.add|Pages.add', 
'Fields.remove|Pages.remove', @id, 1, 1, NOW());
SET @id := @id + 1;
-- end

-- Custom Fields - Student
DELETE FROM `security_functions` WHERE `id` = @id;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Student', 'StudentCustomFields', 'Administration', 'Custom Fields', 5000, 
'Fields.index|Fields.view|Pages.index|Pages.view', 
'Fields.edit|Pages.edit', 
'Fields.add|Pages.add', 
'Fields.remove|Pages.remove', @id, 1, 1, NOW());
SET @id := @id + 1;
-- end

-- Custom Fields - Staff
DELETE FROM `security_functions` WHERE `id` = @id;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Staff', 'StaffCustomFields', 'Administration', 'Custom Fields', 5000, 
'Fields.index|Fields.view|Pages.index|Pages.view', 
'Fields.edit|Pages.edit', 
'Fields.add|Pages.add', 
'Fields.remove|Pages.remove', @id, 1, 1, NOW());
SET @id := @id + 1;
-- end

-- Custom Fields - Infrastructure
DELETE FROM `security_functions` WHERE `id` = @id;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Infrastructure', 'Infrastructures', 'Administration', 'Custom Fields', 5000, 
'Fields.index|Fields.view|Pages.index|Pages.view|Levels.index|Levels.view|Types.index|Types.view', 
'Fields.edit|Pages.edit|Levels.edit|Types.edit', 
'Fields.add|Pages.add|Levels.add|Types.add', 
'Fields.remove|Pages.remove|Levels.remove|Types.remove', @id, 1, 1, NOW());
SET @id := @id + 1;
-- end

-- Translations
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Translations' AND `name` = 'Translations';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Translations', 'Translations', 'Administration', 'Translations', 5000, 
'index|view', 
'edit', 
'add', 
'remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Configurations
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Configurations' AND `name` = 'Configurations';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Configurations', 'Configurations', 'Administration', 'System Configurations', 5000, 
'index|view', 
'edit', 
NULL, 
NULL, NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Notices
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Notices' AND `name` = 'Notices';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Notices', 'Notices', 'Administration', 'Notices', 5000, 
'index|view', 
'edit', 
'add', 
'remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Security: Users
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Securities' AND `name` = 'Users';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Users', 'Securities', 'Administration', 'Security', 5000, 
'Users.index|Users.view|Accounts.index|Accounts.view', 
'Users.edit|Accounts.edit', 
'Users.add', 
'Users.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Security: Groups
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Securities' AND `name` = 'Groups';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Groups', 'Securities', 'Administration', 'Security', 5000, 
'UserGroups.index|UserGroups.view|SystemGroups.index|SystemGroups.view', 
'UserGroups.edit|SystemGroups.edit', 
'UserGroups.add', 
'UserGroups.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Security: User Roles
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Securities' AND `name` = 'User Roles';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'User Roles', 'Securities', 'Administration', 'Security', 5000, 
'Roles.index|Roles.view|UserRoles.view|Permissions.index', 
'Roles.edit|UserRoles.edit|Permissions.edit', 
'Roles.add|UserRoles.add', 
'Roles.remove|UserRoles.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Security: System Roles
DELETE FROM `security_functions` WHERE `id` IN (@id);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'System Roles', 'Securities', 'Administration', 'Security', 5000, 
'Roles.index|Roles.view|SystemRoles.view|Permissions.index', 
'Roles.edit|SystemRoles.edit|Permissions.edit', 
'Roles.add|SystemRoles.add', 
'Roles.remove|SystemRoles.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Survey: Questions
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Surveys' AND `name` = 'Questions' AND `module` = 'Administration';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Questions', 'Surveys', 'Administration', 'Survey', 5000, 
'Questions.index|Questions.view', 
'Questions.edit', 
'Questions.add', 
'Questions.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Survey: Forms
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Surveys' AND `name` = 'Forms' AND `module` = 'Administration';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Forms', 'Surveys', 'Administration', 'Survey', 5000, 
'Forms.index|Forms.view', 
'Forms.edit', 
'Forms.add', 
'Forms.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Survey: Status
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Surveys' AND `name` = 'Status' AND `module` = 'Administration';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Status', 'Surveys', 'Administration', 'Survey', 5000, 
'Status.index|Status.view', 
'Status.edit', 
'Status.add', 
'Status.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Communications: Questions
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Alerts' AND `name` = 'Questions' AND `module` = 'Administration';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Questions', 'Alerts', 'Administration', 'Communications', 5000, 
'Questions.index|Questions.view', 
'Questions.edit', 
'Questions.add', 
'Questions.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Communications: Responses
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Alerts' AND `name` = 'Responses' AND `module` = 'Administration';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Responses', 'Alerts', 'Administration', 'Communications', 5000, 
'Responses.index', 
NULL, 
NULL, 
NULL, NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Communications: Logs
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Alerts' AND `name` = 'Logs' AND `module` = 'Administration';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Logs', 'Alerts', 'Administration', 'Communications', 5000, 
'Logs.index', 
NULL, 
NULL, 
NULL, NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Database: Backup
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Database' AND `name` = 'Backup' AND `module` = 'Administration';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Backup', 'Database', 'Administration', 'Database', 5000, 
NULL, 
NULL, 
NULL, 
NULL, 'backup', @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Database: Restore
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Database' AND `name` = 'Restore' AND `module` = 'Administration';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Restore', 'Database', 'Administration', 'Database', 5000, 
NULL, 
NULL, 
NULL, 
NULL, 'restore', @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Rubrics: Setup
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Rubrics' AND `name` = 'Setup' AND `module` = 'Administration';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Setup', 'Rubrics', 'Administration', 'Rubrics', 5000, 
'Templates.index|Templates.view|Sections.index|Sections.view|Criterias.index|Criterias.view|Options.index|Options.view', 
'Templates.edit|Sections.edit|Criterias.edit|Options.edit', 
'Templates.add|Sections.add|Criterias.add|Options.add', 
'Templates.remove|Sections.remove|Criterias.remove|Options.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Rubrics: Status
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Rubrics' AND `name` = 'Status' AND `module` = 'Administration';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Status', 'Rubrics', 'Administration', 'Rubrics', 5000, 
'Status.index|Status.view', 
'Status.edit', 
'Status.add', 
'Status.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Workflows: Workflows
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Workflows' AND `name` = 'Workflows' AND `module` = 'Administration';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Workflows', 'Workflows', 'Administration', 'Workflows', 5000, 
'Workflows.index|Workflows.view', 
'Workflows.edit', 
'Workflows.add', 
'Workflows.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- Workflows: Steps
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Workflows' AND `name` = 'Steps' AND `module` = 'Administration';
DELETE FROM `security_functions` WHERE `id` IN (@id, @funcId);
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Steps', 'Workflows', 'Administration', 'Workflows', 5000, 
'Steps.index|Steps.view', 
'Steps.edit', 
'Steps.add', 
'Steps.remove', NULL, @id, 1, 1, NOW());
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;
-- end

-- DELETE ALL UNUSED FUNCTIONS
DELETE FROM `security_functions` WHERE `id` < 1000;

-- for class security
UPDATE `security_functions` SET 
`name` = 'All Classes'
WHERE `id` = 1006;

-- shift all functions down by 1
UPDATE `security_functions` SET
`id` = `id` + 1,
`order` = `order` + 1
WHERE `id` > 1006 AND `id` < 2000
ORDER BY `id` DESC;

-- insert a new functions after All Classes
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (1007, 'My Classes', 'Institutions', 'Institutions', 'Details', 1000, 
'MyClasses.index|MyClasses.view|Sections.index|Sections.view', 
'MyClasses.edit|Sections.edit', 
NULL, 
NULL, NULL, 1007, 1, 1, NOW());

-- update role function mapping
UPDATE `security_role_functions` SET
`security_function_id` = `security_function_id` + 1
WHERE `security_function_id` > 1006 AND `security_function_id` < 2000
ORDER BY `security_function_id` DESC;
-- end class security

-- Clean up missing functions from roles
DELETE FROM `security_role_functions` 
WHERE NOT EXISTS (SELECT 1 FROM `security_functions` WHERE `security_functions`.`id` = `security_role_functions`.`security_function_id`);


-- PHPOE-1716
-- Backup institution site section students
Drop Table IF EXISTS `z_1716_institution_site_section_students`;

CREATE TABLE `z_1716_institution_site_section_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_category_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3334 DEFAULT CHARSET=utf8;

INSERT INTO `z_1716_institution_site_section_students` 
SELECT `id`, `student_category_id`
FROM `institution_site_section_students`;

-- Backup field option values
Drop Table IF EXISTS `z_1716_field_option_values`;

CREATE TABLE `z_1716_field_option_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=244 DEFAULT CHARSET=utf8;

INSERT INTO `z_1716_field_option_values` 
SELECT `id`, `name`, `visible`, `editable`, `default`
FROM `field_option_values`;

-- Set field options for StudentCategories to be not visible
UPDATE `field_options`
SET `field_options`.`visible` = 0
WHERE `field_options`.`code` = 'StudentCategories';

-- Set visibility of all record to not visible
UPDATE `field_option_values`
SET `field_option_values`.`visible`=0, `field_option_values`.`default`=0
WHERE `field_option_values`.`field_option_id` = 
  ( SELECT `field_options`.`id`
    FROM `field_options`
    WHERE `field_options`.`code` = 'StudentCategories');

-- Add Promoted Options into field option value
INSERT INTO `field_option_values` (`name`, `order`, `visible`, `editable`, `default`, `field_option_id`, `created_user_id`, `created`)
VALUES ('Promoted', '0', '1', '0', '1', 
  (SELECT `field_options`.`id` FROM field_options WHERE `field_options`.`code` = 'StudentCategories')
    , '1', NOW());

-- Set visibility for Promoted and Visible
UPDATE `field_option_values`
SET `field_option_values`.`visible`=1, `field_option_values`.`editable`=0
WHERE 
  `field_option_values`.`field_option_id` = 
    ( SELECT `field_options`.`id`
      FROM field_options 
      WHERE `field_options`.`code` = 'StudentCategories')
  AND `field_option_values`.`name` = 'Promoted'
  OR `field_option_values`.`name` = 'Repeated';

-- Set the orphan record
UPDATE institution_site_section_students
SET student_category_id = (
	SELECT `field_option_values`.`id`
    FROM `field_option_values`, `field_options`
	WHERE `field_option_values`.`field_option_id` = `field_options`.`id`
		AND `field_options`.`code` = 'StudentCategories'
    AND `field_option_values`.`default` = 1
);

-- PHPOE-1797
-- www_openemis_jor middle name and third name not indexed
ALTER TABLE `security_users` ADD INDEX(`middle_name`);
ALTER TABLE `security_users` ADD INDEX(`third_name`);

-- DB version
UPDATE `config_items` SET `value` = '3.0.5' WHERE `code` = 'db_version';
-- end DB version
