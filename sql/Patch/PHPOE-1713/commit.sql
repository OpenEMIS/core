DELETE FROM `security_functions` WHERE `controller` = 'Census' AND `category` = 'Totals';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Structure';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Grade - Subjects';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Certifications';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Field of Study';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Programme Orientations';
DELETE FROM `security_functions` WHERE `controller` IN ('InfrastructureLevels', 'InfrastructureTypes', 'InfrastructureCustomFields');
DELETE FROM `security_functions` WHERE `controller` = 'Dashboards' AND `name` = 'Dashboards';

UPDATE `security_functions` SET `name` = 'Setup' WHERE `controller` = 'Education' AND `name` = 'Education Subjects';
UPDATE `security_functions` SET `name` = 'Assessments' WHERE `controller` = 'Assessments' AND `name` = 'Items';
UPDATE `security_functions` SET `controller` = 'FieldOptions' WHERE `controller` = 'FieldOption' AND `name` = 'Setup';
UPDATE `security_functions` SET `controller` = 'Configurations' WHERE `controller` = 'Config' AND `name` = 'Configurations';

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









































