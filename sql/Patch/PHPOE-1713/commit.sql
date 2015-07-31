DELETE FROM `security_functions` WHERE `controller` = 'Census' AND `category` = 'Totals';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Structure';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Grade - Subjects';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Certifications';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Field of Study';
DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Programme Orientations';

SET @funcId := 0;

SET @id := 5000;

-- Administration Module

-- Area Levels
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Areas' AND `name` = 'Area Levels';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Levels.index|Levels.view|AdministrativeLevels.index|AdministrativeLevels.view', `_edit` = 'Levels.edit|AdministrativeLevels.edit', `_add` = 'Levels.add|AdministrativeLevels.add', `_delete` = 'Levels.remove|AdministrativeLevels.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Areas
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Areas' AND `name` = 'Areas';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Areas.index|Areas.view|Administratives.index|Administratives.view', `_edit` = 'Areas.edit|Administratives.edit', `_add` = 'Areas.add|Administratives.add', `_delete` = 'Areas.remove|Administratives.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Period Levels
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'AcademicPeriods' AND `name` = 'Academic Period Levels';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Levels.index|Levels.view', `_edit` = 'Levels.edit', `_add` = 'Levels.add', `_delete` = 'Levels.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Periods
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'AcademicPeriods' AND `name` = 'Academic Periods';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Periods.index|Periods.view', `_edit` = 'Periods.edit', `_add` = 'Periods.add', `_delete` = 'Periods.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Education System
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Systems';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Systems.index|Systems.view', `_edit` = 'Systems.edit', `_add` = 'Systems.add', `_delete` = 'Systems.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Education Level
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Levels';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Levels.index|Levels.view', `_edit` = 'Levels.edit', `_add` = 'Levels.add', `_delete` = 'Levels.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Education Cycle
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Cycles';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Cycles.index|Cycles.view', `_edit` = 'Cycles.edit', `_add` = 'Cycles.add', `_delete` = 'Cycles.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Education Programme
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Programmes';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Programmes.index|Programmes.view', `_edit` = 'Programmes.edit', `_add` = 'Programmes.add', `_delete` = 'Programmes.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Education Grade
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Grades';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Grades.index|Grades.view', `_edit` = 'Grades.edit', `_add` = 'Grades.add', `_delete` = 'Grades.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Education Setup
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Subjects';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Subjects.index|Subjects.view|Certifications.index|Certifications.view|FieldOfStudies.index|FieldOfStudies.view|ProgrammeOrientations.index|ProgrammeOrientations.view', 
`_edit` = 'Subjects.edit|Certifications.edit|FieldOfStudies.edit|ProgrammeOrientations.edit', 
`_add` = 'Subjects.add|Certifications.add|FieldOfStudies.add|ProgrammeOrientations.add', 
`_delete` = 'Subjects.remove|Certifications.remove|FieldOfStudies.remove|ProgrammeOrientations.remove', 
`_execute` = NULL,
`name` = 'Setup'
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Assessments
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Assessments' AND `name` = 'Items';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Assessments.index|Assessments.view',
`_edit` = 'Assessments.edit',
`_add` = 'Assessments.add',
`_delete` = 'Assessments.remove',
`_execute` = NULL,
`name` = 'Assessments'
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

DELETE FROM `security_functions` WHERE `id` = @id;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`)
VALUES (@id, 'Grading Types', 'Assessments', 'Administration', 'Assessments', 5000, 'GradingTypes.index|GradingTypes.view|GradingOptions.index|GradingOptions.view', 'GradingTypes.edit|GradingOptions.edit', 'GradingTypes.add|GradingOptions.add', 'GradingTypes.remove|GradingOptions.remove', @id, 1, 1, NOW());
SET @id := @id + 1;

SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Assessments' AND `name` = 'Status';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Status.index|Status.view',
`_edit` = 'Status.edit',
`_add` = 'Status.add',
`_delete` = 'Status.remove',
`_execute` = NULL,
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;























