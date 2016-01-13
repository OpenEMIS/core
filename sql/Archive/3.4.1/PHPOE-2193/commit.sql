-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2193', NOW());

-- `user_activities`
CREATE TABLE `user_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(200) NOT NULL,
  `model_reference` int(11) NOT NULL,
  `field` varchar(200) NOT NULL,
  `field_type` varchar(128) NOT NULL,
  `old_value` varchar(255) NOT NULL,
  `new_value` varchar(255) NOT NULL,
  `operation` varchar(10) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `model_reference` (`model_reference`),
  KEY `security_user_id` (`security_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- student_activities
ALTER TABLE `student_activities` 
RENAME TO  `z_2193_student_activities` ;

-- staff_activites
ALTER TABLE `staff_activities` 
RENAME TO  `z_2193_staff_activities` ;

-- guardian_activities
ALTER TABLE `guardian_activities` 
RENAME TO  `z_2193_guardian_activities` ;

-- security_functions
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - General' WHERE `id`=2000;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - General' WHERE `id`=2001;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - General' WHERE `id`=2002;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - General' WHERE `id`=2003;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - General' WHERE `id`=2004;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - General' WHERE `id`=2005;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - General' WHERE `id`=2006;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - Academic' WHERE `id`=2007;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - General' WHERE `id`=2008;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - General' WHERE `id`=2009;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - General' WHERE `id`=2010;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - Academic' WHERE `id`=2011;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - Academic' WHERE `id`=2012;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - Academic' WHERE `id`=2013;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - Academic' WHERE `id`=2014;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - Academic' WHERE `id`=2015;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - Academic' WHERE `id`=2016;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - Academic' WHERE `id`=2017;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - Finance' WHERE `id`=2018;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - Finance' WHERE `id`=2019;
UPDATE `security_functions` SET `module`='Institutions', `category`='Students - General' WHERE `id`=2020;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - General' WHERE `id`=3000;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - General' WHERE `id`=3001;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - General' WHERE `id`=3002;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - General' WHERE `id`=3003;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - General' WHERE `id`=3004;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - General' WHERE `id`=3005;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - General' WHERE `id`=3006;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Career' WHERE `id`=3007;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - General' WHERE `id`=3008;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - General' WHERE `id`=3009;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Professional Development', `_execute`='Qualifications.download' WHERE `id`=3010;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Professional Development' WHERE `id`=3011;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Career' WHERE `id`=3012;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Career' WHERE `id`=3013;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Career' WHERE `id`=3014;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Career' WHERE `id`=3015;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Career' WHERE `id`=3016;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Career' WHERE `id`=3017;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Professional Development' WHERE `id`=3018;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Career' WHERE `id`=3019;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Finance' WHERE `id`=3020;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Professional Development' WHERE `id`=3021;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Professional Development' WHERE `id`=3022;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Finance' WHERE `id`=3023;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Training' WHERE `id`=3024;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Training' WHERE `id`=3025;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - Training' WHERE `id`=3026;
UPDATE `security_functions` SET `module`='Institutions', `category`='Staff - General' WHERE `id`=3027;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7000, 'Overview', 'Directories', 'Directory', 'General', 7000, 'index|view', 'edit', 'add', 'remove', 7000, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `order`, `visible`, `created_user_id`, `created`) VALUES (7001, 'Accounts', 'Directories', 'Directory', 'General', 7000, 'Accounts.view', 'Accounts.edit', 7001, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7002, 'Identities', 'Directories', 'Directory', 'General', 7000, 'Identities.index|Identities.view', 'Identities.edit', 'Identities.add', 'Identities.remove', 7002, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7003, 'Nationalities', 'Directories', 'Directory', 'General', 7000, 'Nationalities.index|Nationalities.view', 'Nationalities.edit', 'Nationalities.add', 'Nationalities.remove', 7003, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7004, 'Languages', 'Directories', 'Directory', 'General', 7000, 'Languages.index|Languages.view', 'Languages.edit', 'Languages.add', 'Languages.remove', 7004, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7005, 'Comments', 'Directories', 'Directory', 'General', 7000, 'Comments.index|Comments.view', 'Comments.edit', 'Comments.add', 'Comments.remove', 7005, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (7006, 'Attachments', 'Directories', 'Directory', 'General', 7000, 'Attachments.index|Attachments.view', 'Attachments.edit', 'Attachments.add', 'Attachments.remove', 'Attachments.download', 7006, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7007, 'Special Needs', 'Directories', 'Directory', 'General', 7000, 'SpecialNeeds.index|SpecialNeeds.view', 'SpecialNeeds.edit', 'SpecialNeeds.add', 'SpecialNeeds.remove', 7007, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7008, 'History', 'Directories', 'Directory', 'General', 7000, 'History.index|History.view', 'History.edit', 'History.add', 'History.remove', 7008, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7009, 'Guardians', 'Directories', 'Directory', 'Students - Guardians', 7000, 'StudentGuardians.index|StudentGuardians.view', 'StudentGuardians.edit', 'StudentGuardians.add', 'StudentGuardians.remove', 7009, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7010, 'Programmes', 'Directories', 'Directory', 'Students - Academic', 7000, 'StudentProgrammes.index', null, null, null, 7010, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7011, 'Classes', 'Directories', 'Directory', 'Students - Academic', 7000, 'StudentClasses.index', null, null, null, 7011, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7012, 'Subjects', 'Directories', 'Directory', 'Students - Academic', 7000, 'StudentSubjects.index', null, null, null, 7012, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7013, 'Absences', 'Directories', 'Directory', 'Students - Academic', 7000, 'StudentAbsences.index', null, null, null, 7013, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7014, 'Behaviours', 'Directories', 'Directory', 'Students - Academic', 7000, 'StudentBehaviours.index', null, null, null, 7014, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7015, 'Results', 'Directories', 'Directory', 'Students - Academic', 7000, 'StudentResults.index', null, null, null, 7015, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7016, 'Awards', 'Directories', 'Directory', 'Students - Academic', 7000, 'StudentAwards.index|StudentAwards.view', 'StudentAwards.edit', 'StudentAwards.add', 'StudentAwards.remove', 7016, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7017, 'Extracurriculars', 'Directories', 'Directory', 'Students - Academic', 7000, 'StudentExtracurriculars.index|StudentExtracurriculars.view', 'StudentExtracurriculars.edit', 'StudentExtracurriculars.add', 'StudentExtracurriculars.remove', 7017, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7018, 'Bank Accounts', 'Directories', 'Directory', 'Students - Finance', 7000, 'StudentBankAccounts.index|StudentBankAccounts.view', 'StudentBankAccounts.edit', 'StudentBankAccounts.add', 'StudentBankAccounts.remove', 7018, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7019, 'Fees', 'Directories', 'Directory', 'Students - Finance', 7000, 'StudentFees.index', null, null, null, 7019, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7020, 'Employments', 'Directories', 'Directory', 'Staff - Career', 7000, 'StaffEmployments.index|StaffEmployments.view', 'StaffEmployments.edit', 'StaffEmployments.add', 'StaffEmployments.remove', 7020, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7021, 'Positions', 'Directories', 'Directory', 'Staff - Career', 7000, 'StaffPositions.index', null, null, null, 7021, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7022, 'Classes', 'Directories', 'Directory', 'Staff - Career', 7000, 'StaffSections.index', null, null, null, 7022, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7023, 'Subjects', 'Directories', 'Directory', 'Staff - Career', 7000, 'StaffSubjects.index', null, null, null, 7023, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7024, 'Absences', 'Directories', 'Directory', 'Staff - Career', 7000, 'StaffAbsences.index', null, null, null, 7024, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7025, 'Leaves', 'Directories', 'Directory', 'Staff - Career', 7000, 'StaffLeaves.index|StaffLeaves.view', 'StaffLeaves.edit', 'StaffLeaves.add', 'StaffLeaves.remove', 7025, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7026, 'Behaviours', 'Directories', 'Directory', 'Staff - Career', 7000, 'StaffBehaviours.index', null, null, null, 7026, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7027, 'Awards', 'Directories', 'Directory', 'Staff - Career', 7000, 'StaffAwards.index|StaffAwards.view', 'StaffAwards.edit', 'StaffAwards.add', 'StaffAwards.remove', 7027, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (7028, 'Qualifications', 'Directories', 'Directory', 'Staff - Professional Development', 7000, 'StaffQualifications.index|StaffQualifications.view', 'StaffQualifications.edit', 'StaffQualifications.add', 'StaffQualifications.remove', 'StaffQualifications.download', 7028, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7029, 'Extracurriculars', 'Directories', 'Directory', 'Staff - Professional Development', 7000, 'StaffExtracurriculars.index|StaffExtracurriculars.view', 'StaffExtracurriculars.edit', 'StaffExtracurriculars.add', 'StaffExtracurriculars.remove', 7029, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7030, 'Memberships', 'Directories', 'Directory', 'Staff - Professional Development', 7000, 'StaffMemberships.index|StaffMemberships.view', 'StaffMemberships.edit', 'StaffMemberships.add', 'StaffMemberships.remove', 7030, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7031, 'Licenses', 'Directories', 'Directory', 'Staff - Professional Development', 7000, 'StaffLicenses.index|StaffLicenses.view', 'StaffLicenses.edit', 'StaffLicenses.add', 'StaffLicenses.remove', 7031, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7032, 'Trainings', 'Directories', 'Directory', 'Staff - Professional Development', 7000, 'StaffTrainings.index|StaffTrainings.view', 'StaffTrainings.edit', 'StaffTrainings.add', 'StaffTrainings.remove', 7032, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7033, 'Bank Accounts', 'Directories', 'Directory', 'Staff - Finance', 7000, 'StaffBankAccounts.index|StaffBankAccounts.view', 'StaffBankAccounts.edit', 'StaffBankAccounts.add', 'StaffBankAccounts.remove', 7033, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7034, 'Salaries', 'Directories', 'Directory', 'Staff - Finance', 7000, 'StaffSalaries.index|StaffSalaries.view', 'StaffSalaries.edit', 'StaffSalaries.add', 'StaffSalaries.remove', 7034, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) VALUES (7035, 'Training Results', 'Directories', 'Directory', 'Staff - Training', 7000, 'TrainingResults.index|TrainingResults.view', 'TrainingResults.edit', 'TrainingResults.add', 'TrainingResults.remove', 7035, 1, 1, NOW());

-- removal of security_function for guardians module
CREATE TABLE `z_2193_security_function` LIKE `security_functions`;
INSERT INTO `z_2193_security_function` SELECT * FROM `security_functions` WHERE `id` >= 4000 AND `id` < 5000;
DELETE FROM `security_functions` WHERE `id` >= 4000 AND `id` < 5000;

-- removal of security_role_functions
CREATE TABLE `z_2193_security_role_functions` LIKE `security_role_functions`;
INSERT INTO `z_2193_security_role_functions` SELECT * FROM `security_role_functions` WHERE `security_function_id` >= 4000 AND `security_function_id` < 5000;
UPDATE `z_2193_security_role_functions` SET `security_function_id` = 0 WHERE `security_function_id` >= 4000 AND `security_function_id` < 5000;

-- security_functions and security_role_functions (Missing permission for data quality report)
INSERT INTO `z_2193_security_role_functions` SELECT * FROM `security_role_functions` WHERE `security_function_id` = 6006;
UPDATE `security_role_functions` SET `security_function_id` = 6007 WHERE `security_function_id` = 6006;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_add`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (6007, 'Audit', 'Reports', 'Reports', 'Reports', -1, 'Audit.index', 'Audit.add', 'Audit.download', 6007, 1, 1, NOW());
UPDATE `security_functions` SET `name`='Data Quality', `_view`='DataQuality.index', `_add`='DataQuality.add', `_execute`='DataQuality.download' WHERE `id`=6006;
UPDATE `security_functions` SET `name`='Quality' WHERE `id`=6004;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'Results', 'assessment_grading_option_id', 'Student -> Results', 'Grade', 1, 0, NOW());

