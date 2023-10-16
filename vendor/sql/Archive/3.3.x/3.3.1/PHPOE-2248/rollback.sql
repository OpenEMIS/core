-- institution_student_surveys
ALTER TABLE `institution_student_surveys` CHANGE `status_id` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '-1 -> Expired, 0 -> New, 1 -> Draft, 2 -> Completed';

-- custom_modules
UPDATE `custom_modules` SET `name` = 'Institution - Overview' WHERE `model` = 'Institution.Institutions';
UPDATE `custom_modules` SET `name` = 'Student - Overview' WHERE `model` = 'Student.Students';
UPDATE `custom_modules` SET `name` = 'Staff - Overview' WHERE `model` = 'Staff.Staff';
UPDATE `custom_modules` SET `code` = 'Student List', `name` = 'Institution - Student List' WHERE `model` = 'Student.StudentSurveys';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2248';
