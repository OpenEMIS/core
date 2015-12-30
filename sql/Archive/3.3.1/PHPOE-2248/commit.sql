-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2248', NOW());

-- institution_student_surveys
ALTER TABLE `institution_student_surveys` CHANGE `status` `status_id` INT(11) NOT NULL COMMENT 'links to workflow_steps.id';

-- custom_modules
UPDATE `custom_modules` SET `name` = 'Institution > Overview' WHERE `model` = 'Institution.Institutions';
UPDATE `custom_modules` SET `name` = 'Student > Overview' WHERE `model` = 'Student.Students';
UPDATE `custom_modules` SET `name` = 'Staff > Overview' WHERE `model` = 'Staff.Staff';
UPDATE `custom_modules` SET `code` = 'Institution > Students', `name` = 'Institution > Students > Survey' WHERE `model` = 'Student.StudentSurveys';
