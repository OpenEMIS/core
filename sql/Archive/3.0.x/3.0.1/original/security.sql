-- 27th June 2015
UPDATE `security_functions` SET 
	`_edit` = REPLACE(`_edit`, '_view:', ''), 
	`_add` = REPLACE(`_add`, '_view:', ''), 
	`_delete` = REPLACE(`_delete`, '_view:', ''),
	`_execute` = REPLACE(`_execute`, '_view:', '');

UPDATE `security_functions` SET `controller` = 'Institutions' WHERE `controller` = 'InstitutionSites';
UPDATE `security_functions` SET `controller` = 'Institutions', `_view` = 'Students.index|Students.view', `_add` = 'Students.add', `_edit` = 'Students.edit', `_delete` = 'Students.remove', `_execute` = 'Students.excel' WHERE `controller` = 'Students' AND `module` = 'Institutions' AND `category` = 'Details' AND `name` = 'Student';

-- Hide all 'More' functions and 'Census' functions
UPDATE `security_functions` SET `visible` = -1 
WHERE `controller` = 'Census';

DELETE FROM `security_functions` WHERE `name` IN ('More', 'Students - Academic', 'Staff - Academic', 'Population', 'Dashboard Image');
DELETE FROM `security_functions` WHERE `name` = 'Finance' AND `controller` = 'Finance';

-- fixed names and categories
UPDATE `security_functions` SET `name` = 'Students', `category` = 'Students' WHERE `controller` = 'Institutions' AND `name` = 'Student' AND `category` = 'Details';
UPDATE `security_functions` SET `name` = 'Behaviour', `category` = 'Students' WHERE `controller` = 'Institutions' AND `name` = 'Students' AND `category` = 'Behaviour';
UPDATE `security_functions` SET `name` = 'Attendance', `category` = 'Students' WHERE `controller` = 'Institutions' AND `name` = 'Students' AND `category` = 'Attendance';
UPDATE `security_functions` SET `category` = 'Students' WHERE `controller` = 'Institutions' AND `name` = 'Results' AND `category` = 'Assessment';
UPDATE `security_functions` SET `controller` = 'Institutions', `name` = 'Staff', `category` = 'Staff' WHERE `controller` = 'Staff' AND `name` = 'Staff' AND `category` = 'Details';
UPDATE `security_functions` SET `name` = 'Behaviour', `category` = 'Staff' WHERE `controller` = 'Institutions' AND `name` = 'Staff' AND `category` = 'Behaviour';
UPDATE `security_functions` SET `name` = 'Attendance', `category` = 'Staff' WHERE `controller` = 'Institutions' AND `name` = 'Staff' AND `category` = 'Attendance';
UPDATE `security_functions` SET `name` = 'Students' WHERE `controller` = 'Students' AND `name` = 'Student' AND `category` = 'General';

-- reorganise functions
SET @funcId := 0;

SET @id := 1000;
-- Institutions Module
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Institution';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'index|view|dashboard', `_edit` = 'edit', `_add` = 'add', `_delete` = 'remove', `_execute` = 'excel'
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- History
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'History';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'index', `_edit` = null, `_add` = null, `_delete` = null, `visible` = 1 
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Attachments
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Attachments';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Attachments.index|Attachments.view', `_add` = 'Attachments.add', `_edit` = 'Attachments.edit', `_delete` = 'Attachments.remove', `_execute` = 'Attachments.download'
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 1. Details

-- Positions
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Positions';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Positions.index|Positions.view', `_add` = 'Positions.add', `_edit` = 'Positions.edit', `_delete` = 'Positions.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Programmes
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Programmes';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Programmes.index|Programmes.view', `_add` = 'Programmes.add', `_edit` = 'Programmes.edit', `_delete` = 'Programmes.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Shifts
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Shifts';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Shifts.index|Shifts.view', `_add` = 'Shifts.add', `_edit` = 'Shifts.edit', `_delete` = 'Shifts.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Sections
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Sections';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Sections.index|Sections.view', `_add` = 'Sections.add', `_edit` = 'Sections.edit', `_delete` = 'Sections.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Classes
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Classes';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Classes.index|Classes.view', `_add` = 'Classes.add', `_edit` = 'Classes.edit', `_delete` = 'Classes.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Infrastructures
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Infrastructure';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Infrastructures.index|Infrastructures.view', `_add` = 'Infrastructures.add', `_edit` = 'Infrastructures.edit', `_delete` = 'Infrastructures.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;


-- 2. Students
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Students' AND `category` = 'Students';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Students.index|Students.view', `_add` = 'Students.add', `_edit` = 'Students.edit', `_delete` = 'Students.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Student Behaviour
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Behaviour' AND `category` = 'Students';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'StudentBehaviours.index|StudentBehaviours.view', `_add` = 'StudentBehaviours.add', `_edit` = 'StudentBehaviours.edit', `_delete` = 'StudentBehaviours.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Student Attendance
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Attendance' AND `category` = 'Students';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'StudentAttendances.index|StudentAbsences.index|StudentAbsences.view', `_add` = 'StudentAbsences.add', `_edit` = 'StudentAttendances.edit|StudentAbsences.edit', `_delete` = 'StudentAbsences.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Student Results
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Results' AND `category` = 'Students';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Assessments.index|Results.index', `_add` = 'Results.add', `_edit` = 'Results.edit', `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;


-- 3. Staff
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Staff' AND `category` = 'Staff';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Staff.index|Staff.view', `_add` = 'Staff.add', `_edit` = 'Staff.edit', `_delete` = 'Staff.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Staff Behaviour
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Behaviour' AND `category` = 'Staff';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'StaffBehaviours.index|StaffBehaviours.view', `_add` = 'StaffBehaviours.add', `_edit` = 'StaffBehaviours.edit', `_delete` = 'StaffBehaviours.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Staff Attendance
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Attendance' AND `category` = 'Staff';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'StaffAttendances.index|StaffAbsences.index|StaffAbsences.view', `_add` = 'StaffAbsences.add', `_edit` = 'StaffAttendances.edit|StaffAbsences.edit', `_delete` = 'StaffAbsences.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 4 . Finance

-- Bank Accounts
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Bank Accounts' AND `category` = 'Finance';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'BankAccounts.index|BankAccounts.view', `_add` = 'BankAccounts.add', `_edit` = 'BankAccounts.edit', `_delete` = 'BankAccounts.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Fees
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Fees' AND `category` = 'Finance';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Fees.index|Fees.view', `_add` = 'Fees.add', `_edit` = 'Fees.edit', `_delete` = 'Fees.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Student Fees
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Students' AND `category` = 'Finance';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'StudentFees.index|StudentFees.view', `_add` = 'StudentFees.add', `_edit` = 'StudentFees.edit', `_delete` = 'StudentFees.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;


-- 5. Surveys

-- Student Module
DELETE FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Search';

SET @id := 2000;

-- 1. Overview
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Students' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'index|view|Accounts.view', `_edit` = 'edit|Accounts.edit', `_add` = 'add', `_delete` = 'remove', `_execute` = 'excel'
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 2. Contacts
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Contacts' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Contacts.index|Contacts.view', `_edit` = 'Contacts.edit', `_add` = 'Contacts.add', `_delete` = 'Contacts.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 3. Identities
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Identities' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Identities.index|Identities.view', `_edit` = 'Identities.edit', `_add` = 'Identities.add', `_delete` = 'Identities.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 4. Nationalities
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Nationalities' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Nationalities.index|Nationalities.view', `_edit` = 'Nationalities.edit', `_add` = 'Nationalities.add', `_delete` = 'Nationalities.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 5. Languages
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Languages' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Languages.index|Languages.view', `_edit` = 'Languages.edit', `_add` = 'Languages.add', `_delete` = 'Languages.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 6. Comments
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Comments' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Comments.index|Comments.view', `_edit` = 'Comments.edit', `_add` = 'Comments.add', `_delete` = 'Comments.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 7. Special Needs
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Special Needs' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'SpecialNeeds.index|SpecialNeeds.view', `_edit` = 'SpecialNeeds.edit', `_add` = 'SpecialNeeds.add', `_delete` = 'SpecialNeeds.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 8. Awards
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Awards' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Awards.index|Awards.view', `_edit` = 'Awards.edit', `_add` = 'Awards.add', `_delete` = 'Awards.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 9. Attachments
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Attachments' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Attachments.index|Attachments.view', `_edit` = 'Attachments.edit', `_add` = 'Attachments.add', `_delete` = 'Attachments.remove', `_execute` = 'Attachments.download'
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 10. History
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'History' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'History.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL, `visible` = 1
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Student Details
-- 1. Guardians
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Guardians' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Guardians.index|Guardians.view', `_edit` = 'Guardians.edit', `_add` = 'Guardians.add', `_delete` = 'Guardians.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 2. Programmes
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Programmes' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Programmes.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 3. Sections
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Sections' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Sections.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 4. Classes
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Classes' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Classes.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 5. Absence
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Absence' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Absences.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 6. Behaviour
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Behaviour' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Behaviours.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 7. Results
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Results' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Results.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 8. Extracurricular
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Extracurricular' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Extracurriculars.index|Extracurriculars.view', `_edit` = 'Extracurriculars.edit', `_add` = 'Extracurriculars.add', `_delete` = 'Extracurriculars.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Student Finance

-- 1. Bank Accounts
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Bank Accounts' AND `category` = 'Finance';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'BankAccounts.index|BankAccounts.view', `_edit` = 'BankAccounts.edit', `_add` = 'BankAccounts.add', `_delete` = 'BankAccounts.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 2. Fees
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Fees' AND `category` = 'Finance';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Fees.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Removing Student Health permissions
DELETE FROM `security_functions` WHERE `controller` = 'Students' AND `category` = 'Health';

-- Update Student parent ids
UPDATE `security_functions` SET `parent_id` = 2000 WHERE `controller` = 'Students' AND `parent_id` <> -1;

-- SET @id := 2011;
-- SET @funcId := @id - 1;
-- UPDATE `security_functions` SET `id` = @id WHERE `id` = @funcId;
-- UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;

-- Staff Module
DELETE FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Search';

SET @id := 3000;

-- 1. Overview
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Staff' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'index|view|Accounts.view', `_edit` = 'edit|Accounts.edit', `_add` = 'add', `_delete` = 'remove', `_execute` = 'excel'
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 2. Contacts
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Contacts' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Contacts.index|Contacts.view', `_edit` = 'Contacts.edit', `_add` = 'Contacts.add', `_delete` = 'Contacts.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 3. Identities
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Identities' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Identities.index|Identities.view', `_edit` = 'Identities.edit', `_add` = 'Identities.add', `_delete` = 'Identities.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 4. Nationalities
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Nationalities' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Nationalities.index|Nationalities.view', `_edit` = 'Nationalities.edit', `_add` = 'Nationalities.add', `_delete` = 'Nationalities.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 5. Languages
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Languages' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Languages.index|Languages.view', `_edit` = 'Languages.edit', `_add` = 'Languages.add', `_delete` = 'Languages.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 6. Comments
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Comments' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Comments.index|Comments.view', `_edit` = 'Comments.edit', `_add` = 'Comments.add', `_delete` = 'Comments.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 7. Special Needs
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Special Needs' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'SpecialNeeds.index|SpecialNeeds.view', `_edit` = 'SpecialNeeds.edit', `_add` = 'SpecialNeeds.add', `_delete` = 'SpecialNeeds.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 8. Awards
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Awards' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Awards.index|Awards.view', `_edit` = 'Awards.edit', `_add` = 'Awards.add', `_delete` = 'Awards.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 9. Attachments
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Attachments' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Attachments.index|Attachments.view', `_edit` = 'Attachments.edit', `_add` = 'Attachments.add', `_delete` = 'Attachments.remove', `_execute` = 'Attachments.download'
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 10. History
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'History' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'History.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL, `visible` = 1
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Staff Details

-- 1. Qualifications
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Qualifications' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Qualifications.index|Qualifications.view', `_edit` = 'Qualifications.edit', `_add` = 'Qualifications.add', `_delete` = 'Qualifications.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 2. Trainings
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Training' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Trainings.index|Trainings.view', `_edit` = 'Trainings.edit', `_add` = 'Trainings.add', `_delete` = 'Trainings.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 3. Positions
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Positions' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Positions.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 4. Sections
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Sections' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Sections.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 5. Classes
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Classes' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Classes.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 6. Absence
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Absence' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Absences.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 7. Leaves
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Leave' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Leaves.index|Leaves.view', `_edit` = 'Leaves.edit', `_add` = 'Leaves.add', `_delete` = 'Leaves.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 8. Behaviour
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Behaviour' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Behaviours.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 9. Extracurricular
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Extracurricular' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Extracurriculars.index|Extracurriculars.view', `_edit` = 'Extracurriculars.edit', `_add` = 'Extracurriculars.add', `_delete` = 'Extracurriculars.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 10. Employments
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Employment' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Employments.index|Employments.view', `_edit` = 'Employments.edit', `_add` = 'Employments.add', `_delete` = 'Employments.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 11. Salary
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Salary' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Salaries.index|Salaries.view', `_edit` = 'Salaries.edit', `_add` = 'Salaries.add', `_delete` = 'Salaries.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 12. Memberships
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Memberships' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Memberships.index|Memberships.view', `_edit` = 'Memberships.edit', `_add` = 'Memberships.add', `_delete` = 'Memberships.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 13. Licenses
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Licenses' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Licenses.index|Licenses.view', `_edit` = 'Licenses.edit', `_add` = 'Licenses.add', `_delete` = 'Licenses.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Staff Finance

-- 1. Bank Accounts
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Bank Accounts' AND `category` = 'Finance';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'BankAccounts.index|BankAccounts.view', `_edit` = 'BankAccounts.edit', `_add` = 'BankAccounts.add', `_delete` = 'BankAccounts.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Removing Staff Health permissions
DELETE FROM `security_functions` WHERE `controller` = 'Staff' AND `category` = 'Health';

-- Update Student parent ids
UPDATE `security_functions` SET `parent_id` = 3000 WHERE `controller` = 'Staff' AND `parent_id` <> -1;








-- Clean up missing functions from roles
DELETE FROM `security_role_functions` 
WHERE NOT EXISTS (SELECT 1 FROM `security_functions` WHERE `security_functions`.`id` = `security_role_functions`.`security_function_id`);
