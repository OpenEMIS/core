DROP TABLE IF EXISTS `training_session_trainers`;
CREATE TABLE IF NOT EXISTS `training_session_trainers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_session_id` int(11) NOT NULL,
  `ref_trainer_id` int(11) NOT NULL,
  `ref_trainer_name` varchar(255) NOT NULL,
  `ref_trainer_table` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_session_id` (`training_session_id`),
  KEY `ref_trainer_id` (`ref_trainer_id`,`ref_trainer_table`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `staff_training_needs`;
CREATE TABLE IF NOT EXISTS `staff_training_needs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `ref_course_id` int(11) NOT NULL,
  `ref_course_table` varchar(100) NOT NULL,
  `ref_course_code` varchar(10) DEFAULT NULL,
  `ref_course_title` varchar(100) DEFAULT NULL,
  `ref_course_description` text,
  `ref_course_requirement` varchar(100) DEFAULT NULL,
  `training_priority_id` int(11) NOT NULL,
  `training_status_id` int(11) NOT NULL DEFAULT '1',
  `comments` text,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `ref_course_id`  (`ref_course_id`,`ref_course_table`),
  KEY `training_priority_id` (`training_priority_id`),
  KEY `training_status_id` (`training_status_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `staff_training_self_study_results`;
CREATE TABLE IF NOT EXISTS `staff_training_self_study_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_training_self_study_id` int(11) NOT NULL,
  `pass` int(1) DEFAULT NULL,
  `result` varchar(10) DEFAULT NULL,
  `training_status_id` int(11) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_training_self_study_id` (`staff_training_self_study_id`),
  KEY `training_status_id` (`training_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `workflows`;
CREATE TABLE IF NOT EXISTS `workflows` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `model_name` varchar(50) NOT NULL,
  `workflow_name` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `approve` varchar(100) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `order` int(3) NOT NULL DEFAULT '0',
  `parent_id` int(11) DEFAULT '0',
  `lft` int(11) DEFAULT NULL,
  `rght` int(11) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


INSERT INTO `workflows` (`id`, `model_name`, `workflow_name`, `action`, `approve`, `visible`, `order`, `parent_id`, `lft`, `rght`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'TrainingCourse', 'Pending for Recommendation', 'Recommend', '', 1, 1, NULL, 1, 2, NULL, '2014-06-17 23:09:29', 1, '2014-04-09 00:00:00'),
(2, 'TrainingCourse', 'Pending for Approval', 'Approve', '', 1, 2, NULL, 31, 32, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(3, 'TrainingCourse', 'Pending for Accreditation', 'Accredit', 'Accredited', 1, 3, NULL, 29, 30, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(4, 'TrainingSession', 'Pending for Recommendation', 'Recommend', '', 1, 1, NULL, 27, 28, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(5, 'TrainingSession', 'Pending for Approval', 'Approve', '', 1, 2, NULL, 25, 26, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(6, 'TrainingSession', 'Pending for Registration', 'Register', 'Registered', 1, 3, NULL, 23, 24, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(7, 'TrainingSessionResult', 'Pending for Evaluation', 'Evaluate', '', 1, 1, NULL, 21, 22, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(8, 'TrainingSessionResult', 'Pending for Approval', 'Approve', '', 1, 2, NULL, 19, 20, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(9, 'TrainingSessionResult', 'Pending for Posting', 'Post', 'Posted', 1, 3, NULL, 17, 18, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(10, 'StaffTrainingNeed', 'Pending for Approval', 'Approve', 'Approved', 1, 1, NULL, 15, 16, NULL, '2014-06-17 23:09:29', 1, '2014-04-11 00:00:00'),
(11, 'StaffTrainingSelfStudy', 'Pending for Recommendation', 'Recommend', '', 1, 1, NULL, 13, 14, NULL, '2014-06-17 23:09:29', 1, '2014-04-11 00:00:00'),
(12, 'StaffTrainingSelfStudy', 'Pending for Approval', 'Approve', '', 1, 2, NULL, 11, 12, NULL, '2014-06-17 23:09:29', 1, '2014-04-11 00:00:00'),
(13, 'StaffTrainingSelfStudy', 'Pending for Accreditation', 'Accredit', 'Accredited', 1, 3, NULL, 3, 10, NULL, '2014-06-17 23:09:29', 1, '2014-04-11 00:00:00'),
(14, 'StaffTrainingSelfStudyResult', 'Pending for Result Recommendation', 'Recommend', '', 1, 1, 13, 6, 7, NULL, '2014-06-17 23:09:29', 1, '2014-06-17 00:00:00'),
(15, 'StaffTrainingSelfStudyResult', 'Pending for Result Approval', 'Approve', '', 1, 2, 13, 4, 5, NULL, '2014-06-17 23:09:29', 1, '2014-06-17 00:00:00'),
(16, 'StaffTrainingSelfStudyResult', 'Pending for Result Accreditation', 'Accredit', 'Result Accredited', 1, 3, 13, 8, 9, NULL, '2014-06-17 23:09:29', 1, '2014-06-17 00:00:00');


DROP TABLE IF EXISTS `staff_training_self_studies`;
CREATE TABLE IF NOT EXISTS `staff_training_self_studies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_achievement_type_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text,
  `objective` text,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `training_provider` varchar(255) NOT NULL,
  `hours` int(3) NOT NULL,
  `credit_hours` int(11) NOT NULL,
  `training_status_id` int(11) NOT NULL DEFAULT '1',
  `staff_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_status_id` (`training_status_id`),
  KEY `training_achievement_type_id` (`training_achievement_type_id`),
  KEY `staff_id` (`staff_id`),
  KEY `training_provider_id` (`training_provider`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

TRUNCATE TABLE field_options;
INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'InstitutionSiteProvider', 'Provider', 'Institution', NULL, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(2, 'InstitutionSiteSector', 'Sector', 'Institution', NULL, 2, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(3, 'InstitutionSiteType', 'Type', 'Institution', '{"model":"InstitutionSiteType"}', 3, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(4, 'InstitutionSiteOwnership', 'Ownership', 'Institution', '{"model":"InstitutionSiteOwnership"}', 4, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(5, 'InstitutionSiteLocality', 'Locality', 'Institution', '{"model":"InstitutionSiteLocality"}', 5, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(6, 'InstitutionSiteStatus', 'Status', 'Institution', '{"model":"InstitutionSiteStatus"}', 6, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(7, 'InstitutionSiteCustomField', 'Custom Fields', 'Institution', '{"model":"InstitutionSiteCustomField"}', 7, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(8, 'InstitutionSiteCustomFieldOption', 'Custom Field Options', 'Institution', '{"model":"InstitutionSiteCustomFieldOption"}', 8, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(9, 'CensusCustomField', 'Custom Fields', 'Institution Totals', '{"model":"CensusCustomField"}', 9, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(10, 'CensusCustomFieldOption', 'Custom Field Options', 'Institution Totals', '{"model":"CensusCustomFieldOption"}', 10, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(11, 'FinanceNature', 'Nature', 'Finance', '{"model":"FinanceNature"}', 11, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(12, 'FinanceType', 'Types', 'Finance', '{"model":"FinanceType"}', 12, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(13, 'FinanceCategory', 'Categories', 'Finance', '{"model":"FinanceCategory"}', 13, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(14, 'FinanceSource', 'Source', 'Finance', '{"model":"FinanceSource"}', 14, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(15, 'Bank', 'Banks', 'Bank', '{"model":"Bank"}', 15, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(16, 'BankBranch', 'Branches', 'Bank', '{"model":"BankBranch"}', 16, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(17, 'AssessmentResultType', 'Result Types', 'Assessment', '{"model":"AssessmentResultType"}', 17, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(18, 'ContactType', 'Types', 'Contact', '{"model":"ContactType"}', 18, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(19, 'EmploymentType', 'Types', 'Employment', '{"model":"EmploymentType"}', 19, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(20, 'ExtracurricularType', 'Types', 'Extracurricular', '{"model":"ExtracurricularType"}', 20, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(21, 'SchoolYear', 'School Year', NULL, '{"model":"SchoolYear"}', 21, 1, NULL, NULL, 2, '0000-00-00 00:00:01'),
(22, 'Country', 'Countries', NULL, '{"model":"Country"}', 22, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(23, 'Language', 'Languages', NULL, '{"model":"Language"}', 23, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(24, 'IdentityType', 'Identity Types', NULL, '{"model":"IdentityType"}', 24, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(25, 'LicenseType', 'License Types', NULL, '{"model":"LicenseType"}', 25, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(26, 'SpecialNeedType', 'Special Need Types', NULL, '{"model":"SpecialNeedType"}', 26, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(27, 'QualityVisitType', 'Visit Types', 'Quality', '{"model":"QualityVisitType"}', 27, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(28, 'HealthRelationship', 'Relationships', 'Health', '{"model":"HealthRelationship"}', 28, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(29, 'HealthCondition', 'Conditions', 'Health', '{"model":"HealthCondition"}', 29, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(30, 'HealthImmunization', 'Immunization', 'Health', '{"model":"HealthImmunization"}', 30, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(31, 'HealthAllergyType', 'Allergy Types', 'Health', '{"model":"HealthAllergyType"}', 31, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(32, 'HealthTestType', 'Test Types', 'Health', '{"model":"HealthTestType"}', 32, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(33, 'HealthConsultationType', 'Consultation Types', 'Health', '{"model":"HealthConsultationType"}', 33, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(34, 'InfrastructureCategory', 'Categories', 'Infrastructure', '{"model":"InfrastructureCategory"}', 34, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(35, 'InfrastructureBuilding', 'Buildings', 'Infrastructure', '{"model":"InfrastructureBuilding"}', 35, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(36, 'InfrastructureEnergy', 'Energy', 'Infrastructure', '{"model":"InfrastructureEnergy"}', 36, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(37, 'InfrastructureFurniture', 'Furniture', 'Infrastructure', '{"model":"InfrastructureFurniture"}', 37, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(38, 'InfrastructureResource', 'Resources', 'Infrastructure', '{"model":"InfrastructureResource"}', 38, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(39, 'InfrastructureRoom', 'Rooms', 'Infrastructure', '{"model":"InfrastructureRoom"}', 39, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(40, 'InfrastructureSanitation', 'Sanitation', 'Infrastructure', '{"model":"InfrastructureSanitation"}', 40, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(41, 'InfrastructureWater', 'Water', 'Infrastructure', '{"model":"InfrastructureWater"}', 41, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(42, 'InfrastructureMaterial', 'Materials', 'Infrastructure', '{"model":"InfrastructureMaterial"}', 42, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(43, 'InfrastructureStatus', 'Statuses', 'Infrastructure', '{"model":"InfrastructureStatus"}', 43, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(44, 'SalaryAdditionType', 'Addition Types', 'Salary', '{"model":"SalaryAdditionType"}', 44, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(45, 'SalaryDeductionType', 'Deduction Types', 'Salary', '{"model":"SalaryDeductionType"}', 45, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(46, 'TrainingCourseType', 'Course Types', 'Training', '{"model":"TrainingCourseType"}', 46, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(47, 'TrainingFieldStudy', 'Field of Studies', 'Training', '{"model":"TrainingFieldStudy"}', 47, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(48, 'TrainingLevel', 'Levels', 'Training', '{"model":"TrainingLevel"}', 48, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(49, 'TrainingModeDelivery', 'Mode of Deliveries', 'Training', '{"model":"TrainingModeDelivery"}', 49, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(50, 'TrainingPriority', 'Priorities', 'Training', '{"model":"TrainingPriority"}', 50, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(51, 'TrainingProvider', 'Providers', 'Training', '{"model":"TrainingProvider"}', 51, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(52, 'TrainingRequirement', 'Requirements', 'Training', '{"model":"TrainingRequirement"}', 52, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(53, 'TrainingStatus', 'Statuses', 'Training', '{"model":"TrainingStatus"}', 53, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(54, 'StudentCategory', 'Categories', 'Student', '{"model":"Students.StudentCategory"}', 54, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(55, 'StudentBehaviourCategory', 'Behaviour Categories', 'Student', '{"model":"Students.StudentBehaviourCategory"}', 55, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(56, 'StudentAttendanceType', 'Attendance Types', 'Student', '{"model":"Students.StudentAttendanceType"}', 56, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(57, 'StudentCustomField', 'Custom Fields', 'Student', '{"model":"Students.StudentCustomField"}', 57, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(58, 'StudentCustomFieldOption', 'Custom Fields Options', 'Student', '{"model":"Students.StudentCustomFieldOptions"}', 58, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(59, 'PositionTitle', 'Titles', 'Position', '{"model":"Staff.StaffPositionTitle"}', 59, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(60, 'PositionGrade', 'Grades', 'Position', '{"model":"Staff.StaffPositionGrade"}', 60, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(61, 'PositionStep', 'Steps', 'Position', '{"model":"Staff.StaffPositionStep"}', 61, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(62, 'StaffTrainingCategory', 'Training Categories', 'Staff', NULL, 62, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(63, 'StudentAbsenceReason', 'Absence Reasons', 'Student', NULL, 63, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(64, 'StaffAbsenceReason', 'Absence Reasons', 'Staff', NULL, 64, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(65, 'StaffType', 'Staff Type', 'Staff', NULL, 65, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(66, 'InstitutionSiteGender', 'Gender', 'Institution', NULL, 66, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(67, 'TrainingNeedCategory', 'Need Categories', 'Training', NULL, 67, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(68, 'TrainingResultType', 'Result Types', 'Training', NULL, 68, 1, NULL, NULL, 1, '2014-06-16 00:00:00'),
(69, 'TrainingAchievementType', 'Achievement Types', 'Training', NULL, 69, 1, NULL, NULL, 1, '2014-06-17 00:00:00');

TRUNCATE TABLE `field_option_values`;
INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'Government', 0, 1, 1, 0, 'government', 'government', 1, NULL, NULL, 1, '2014-05-12 09:35:21'),
(2, 'Test Sector', 0, 1, 1, 0, 'test-secto', 'test-secto', 2, NULL, NULL, 1, '2014-05-12 09:36:18'),
(3, 'Full-Time', 0, 1, 1, 1, '', '', 65, NULL, NULL, 1, '2014-06-04 16:54:58'),
(4, 'Part-Time', 0, 1, 1, 0, '', '', 65, NULL, NULL, 1, '2014-06-04 17:09:17'),
(5, 'Contract', 0, 1, 1, 0, '', '', 65, NULL, NULL, 1, '2014-06-04 17:09:25'),
(6, 'Boys', 0, 1, 1, 0, '', '', 66, NULL, NULL, 1, '2014-06-05 10:48:13'),
(7, 'Girls', 0, 1, 1, 0, '', '', 66, NULL, NULL, 1, '2014-06-05 10:48:18'),
(8, 'Mixed', 0, 1, 1, 0, '', '', 66, NULL, NULL, 1, '2014-06-05 10:48:24'),
(9, 'Math', 1, 1, 1, 0, NULL, NULL, 67, NULL, NULL, 1, '2014-06-11 22:20:47'),
(10, 'Science', 2, 1, 1, 0, NULL, NULL, 67, NULL, NULL, 1, '2014-06-11 22:20:47'),
(11, 'Arts', 3, 1, 1, 0, NULL, NULL, 67, NULL, NULL, 1, '2014-06-11 22:20:47'),
(14, 'Exam', 1, 1, 1, 0, NULL, NULL, 68, NULL, NULL, 1, '2014-06-17 00:00:00'),
(15, 'Practical', 2, 1, 1, 0, NULL, NULL, 68, NULL, NULL, 1, '2014-06-17 00:00:00'),
(16, 'Attendance', 3, 1, 1, 0, NULL, NULL, 68, NULL, NULL, 1, '2014-06-17 00:00:00'),
(17, 'School Based Study', 1, 1, 1, 0, NULL, NULL, 69, NULL, NULL, 1, '2014-06-17 00:00:00'),
(18, 'Self Based Study', 2, 1, 1, 0, NULL, NULL, 69, NULL, NULL, 1, '2014-06-17 00:00:00');

TRUNCATE table workflow_logs;

DROP TABLE IF EXISTS `training_sessions`;
CREATE TABLE IF NOT EXISTS `training_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_course_id` int(11) NOT NULL,
  `training_provider_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `area_id` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `comments` text,
  `training_status_id` int(11) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_course_id` (`training_course_id`),
  KEY `training_provider_id` (`training_provider_id`),
  KEY `area_id` (`area_id`),
  KEY `training_status_id` (`training_status_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `training_courses`;
CREATE TABLE IF NOT EXISTS `training_courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text,
  `objective` text,
  `training_field_study_id` int(11) NOT NULL,
  `training_course_type_id` int(11) NOT NULL,
  `credit_hours` int(3) DEFAULT NULL,
  `duration` int(3) DEFAULT NULL,
  `training_mode_delivery_id` int(11) NOT NULL,
  `training_requirement_id` int(11) NOT NULL,
  `training_level_id` int(11) NOT NULL,
  `training_status_id` int(11) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_field_study_id` (`training_field_study_id`),
  KEY `training_course_type_id` (`training_course_type_id`),
  KEY `training_mode_delivery_id` (`training_mode_delivery_id`),
  KEY `training_requirement_id` (`training_requirement_id`),
  KEY `training_level_id` (`training_level_id`),
  KEY `training_status_id` (`training_status_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


DROP TABLE IF EXISTS `training_course_result_types`;
CREATE TABLE IF NOT EXISTS `training_course_result_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_course_id` int(11) NOT NULL,
  `training_result_type_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_course_id` (`training_course_id`),
  KEY `training_result_type_id` (`training_result_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


DROP TABLE IF EXISTS `training_course_target_populations`;
CREATE TABLE IF NOT EXISTS `training_course_target_populations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_course_id` int(11) NOT NULL,
  `staff_position_title_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_course_id` (`training_course_id`),
  KEY `staff_position_title_id` (`staff_position_title_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

truncate table training_course_attachments;
truncate table training_course_prerequisites;
truncate table training_course_providers;


DROP TABLE IF EXISTS `training_session_results`;
CREATE TABLE IF NOT EXISTS `training_session_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_session_id` int(11) NOT NULL,
  `training_status_id` int(11) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_session_id` (`training_session_id`),
  KEY `training_status_id` (`training_status_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `training_session_trainees`;
CREATE TABLE IF NOT EXISTS `training_session_trainees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_session_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `pass` int(1) DEFAULT NULL,
  `result` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_session_id` (`training_session_id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


DROP TABLE IF EXISTS `training_session_trainee_results`;
CREATE TABLE IF NOT EXISTS `training_session_trainee_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_session_trainee_id` int(11) NOT NULL,
  `training_result_type_id` int(11) NOT NULL,
  `pass` int(1) DEFAULT NULL,
  `result` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_session_trainee_id` (`training_session_trainee_id`),
  KEY `training_result_type_id` (`training_result_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


TRUNCATE table staff_training_self_study_attachments;

ALTER TABLE  `staff_training_self_study_results` ADD UNIQUE (
`staff_training_self_study_id`
);


Update `security_functions` set _edit='_view:resultEdit|resultEdit|resultDownloadTemplate|resultUpload' where `name` ='Training Results' and controller='Training' and module='Training';
