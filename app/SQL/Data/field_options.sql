-- phpMyAdmin SQL Dump
-- version 4.0.4.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jul 24, 2014 at 09:54 AM
-- Server version: 5.6.11
-- PHP Version: 5.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `dev_openemis_demo`
--

-- --------------------------------------------------------

--
-- Table structure for table `field_options`
--

DROP TABLE IF EXISTS `field_options`;
CREATE TABLE IF NOT EXISTS `field_options` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `parent` varchar(50) DEFAULT NULL,
  `params` text,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Dumping data for table `field_options`
--

INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'InstitutionSiteProvider', 'Provider', 'Institution', NULL, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(2, 'InstitutionSiteSector', 'Sector', 'Institution', NULL, 2, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(3, 'InstitutionSiteType', 'Type', 'Institution', '{"model":"InstitutionSiteType"}', 3, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(4, 'InstitutionSiteOwnership', 'Ownership', 'Institution', '{"model":"InstitutionSiteOwnership"}', 4, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(5, 'InstitutionSiteLocality', 'Locality', 'Institution', '{"model":"InstitutionSiteLocality"}', 5, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(6, 'InstitutionSiteStatus', 'Status', 'Institution', '{"model":"InstitutionSiteStatus"}', 7, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(7, 'InstitutionSiteCustomField', 'Custom Fields', 'Institution', '{"model":"InstitutionSiteCustomField"}', 8, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(8, 'InstitutionSiteCustomFieldOption', 'Custom Field Options', 'Institution', '{"model":"InstitutionSiteCustomFieldOption"}', 9, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(9, 'CensusCustomField', 'Custom Fields', 'Institution Totals', '{"model":"CensusCustomField"}', 10, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(10, 'CensusCustomFieldOption', 'Custom Field Options', 'Institution Totals', '{"model":"CensusCustomFieldOption"}', 11, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(11, 'FinanceNature', 'Nature', 'Finance', '{"model":"FinanceNature"}', 12, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(12, 'FinanceType', 'Types', 'Finance', '{"model":"FinanceType"}', 13, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(13, 'FinanceCategory', 'Categories', 'Finance', '{"model":"FinanceCategory"}', 14, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(14, 'FinanceSource', 'Source', 'Finance', '{"model":"FinanceSource"}', 15, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(15, 'Bank', 'Banks', 'Bank', '{"model":"Bank"}', 16, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(16, 'BankBranch', 'Branches', 'Bank', '{"model":"BankBranch"}', 17, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(17, 'AssessmentResultType', 'Result Types', 'Assessment', '{"model":"AssessmentResultType"}', 18, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(18, 'ContactType', 'Types', 'Contact', '{"model":"ContactType"}', 19, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(19, 'EmploymentType', 'Types', 'Employment', '{"model":"EmploymentType"}', 20, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(20, 'ExtracurricularType', 'Types', 'Extracurricular', '{"model":"ExtracurricularType"}', 21, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(21, 'SchoolYear', 'School Year', NULL, '{"model":"SchoolYear"}', 22, 1, NULL, NULL, 2, '0000-00-00 00:00:01'),
(22, 'Country', 'Countries', NULL, '{"model":"Country"}', 23, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(23, 'Language', 'Languages', NULL, '{"model":"Language"}', 24, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(24, 'IdentityType', 'Identity Types', NULL, '{"model":"IdentityType"}', 25, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(25, 'LicenseType', 'License Types', NULL, '{"model":"LicenseType"}', 26, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(26, 'SpecialNeedType', 'Special Need Types', NULL, '{"model":"SpecialNeedType"}', 27, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(27, 'QualityVisitType', 'Visit Types', 'Quality', '{"model":"QualityVisitType"}', 28, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(28, 'HealthRelationship', 'Relationships', 'Health', '{"model":"HealthRelationship"}', 29, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(29, 'HealthCondition', 'Conditions', 'Health', '{"model":"HealthCondition"}', 30, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(30, 'HealthImmunization', 'Immunization', 'Health', '{"model":"HealthImmunization"}', 31, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(31, 'HealthAllergyType', 'Allergy Types', 'Health', '{"model":"HealthAllergyType"}', 32, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(32, 'HealthTestType', 'Test Types', 'Health', '{"model":"HealthTestType"}', 33, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(33, 'HealthConsultationType', 'Consultation Types', 'Health', '{"model":"HealthConsultationType"}', 34, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(34, 'InfrastructureCategory', 'Categories', 'Infrastructure', '{"model":"InfrastructureCategory"}', 35, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(35, 'InfrastructureBuilding', 'Buildings', 'Infrastructure', '{"model":"InfrastructureBuilding"}', 36, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(36, 'InfrastructureEnergy', 'Energy', 'Infrastructure', '{"model":"InfrastructureEnergy"}', 37, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(37, 'InfrastructureFurniture', 'Furniture', 'Infrastructure', '{"model":"InfrastructureFurniture"}', 38, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(38, 'InfrastructureResource', 'Resources', 'Infrastructure', '{"model":"InfrastructureResource"}', 39, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(39, 'InfrastructureRoom', 'Rooms', 'Infrastructure', '{"model":"InfrastructureRoom"}', 40, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(40, 'InfrastructureSanitation', 'Sanitation', 'Infrastructure', '{"model":"InfrastructureSanitation"}', 41, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(41, 'InfrastructureWater', 'Water', 'Infrastructure', '{"model":"InfrastructureWater"}', 42, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(42, 'InfrastructureMaterial', 'Materials', 'Infrastructure', '{"model":"InfrastructureMaterial"}', 43, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(43, 'InfrastructureStatus', 'Statuses', 'Infrastructure', '{"model":"InfrastructureStatus"}', 44, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(44, 'SalaryAdditionType', 'Addition Types', 'Salary', '{"model":"SalaryAdditionType"}', 45, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(45, 'SalaryDeductionType', 'Deduction Types', 'Salary', '{"model":"SalaryDeductionType"}', 46, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(46, 'TrainingCourseType', 'Course Types', 'Training', '{"model":"TrainingCourseType"}', 47, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(47, 'TrainingFieldStudy', 'Field of Studies', 'Training', '{"model":"TrainingFieldStudy"}', 48, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(48, 'TrainingLevel', 'Levels', 'Training', '{"model":"TrainingLevel"}', 49, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(49, 'TrainingModeDelivery', 'Mode of Deliveries', 'Training', '{"model":"TrainingModeDelivery"}', 50, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(50, 'TrainingPriority', 'Priorities', 'Training', '{"model":"TrainingPriority"}', 51, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(51, 'TrainingProvider', 'Providers', 'Training', '{"model":"TrainingProvider"}', 52, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(52, 'TrainingRequirement', 'Requirements', 'Training', '{"model":"TrainingRequirement"}', 53, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(53, 'TrainingStatus', 'Statuses', 'Training', '{"model":"TrainingStatus"}', 54, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(54, 'StudentCategory', 'Categories', 'Student', '{"model":"Students.StudentCategory"}', 55, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(55, 'StudentBehaviourCategory', 'Behaviour Categories', 'Student', '{"model":"Students.StudentBehaviourCategory"}', 56, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(57, 'StudentCustomField', 'Custom Fields', 'Student', '{"model":"Students.StudentCustomField"}', 58, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(58, 'StudentCustomFieldOption', 'Custom Fields Options', 'Student', '{"model":"Students.StudentCustomFieldOption"}', 59, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(59, 'PositionTitle', 'Titles', 'Position', '{"model":"Staff.StaffPositionTitle"}', 60, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(60, 'PositionGrade', 'Grades', 'Position', '{"model":"Staff.StaffPositionGrade"}', 61, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(61, 'PositionStep', 'Steps', 'Position', '{"model":"Staff.StaffPositionStep"}', 62, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(62, 'StaffTrainingCategory', 'Training Categories', 'Staff', NULL, 64, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(63, 'StudentAbsenceReason', 'Absence Reasons', 'Student', NULL, 57, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(64, 'StaffAbsenceReason', 'Absence Reasons', 'Staff', NULL, 65, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(65, 'StaffType', 'Staff Type', 'Staff', NULL, 63, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(66, 'InstitutionSiteGender', 'Gender', 'Institution', NULL, 6, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(67, 'TrainingNeedCategory', 'Need Categories', 'Training', NULL, 68, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(68, 'TrainingResultType', 'Result Types', 'Training', NULL, 69, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(69, 'TrainingAchievementType', 'Achievement Types', 'Training', NULL, 70, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(70, 'FeeType', 'Fee Types', 'Finance', NULL, 71, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(71, 'StaffCustomField', 'Custom Fields', 'Staff', '{"model":"Staff.StaffCustomField"}', 66, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(72, 'StaffCustomFieldOption', 'Custom Fields Options', 'Staff', '{"model":"Staff.StaffCustomFieldOption"}', 67, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(73, 'StaffLeaveType', 'Leave Type', 'Staff', NULL, 72, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(74, 'LeaveStatus', 'Leave Status', 'Staff', NULL, 73, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Sick Leave', 0, 1, 1, 0, NULL, NULL, 73, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Pending', 0, 1, 1, 0, NULL, NULL, 74, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Approved', 0, 1, 1, 0, NULL, NULL, 74, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Rejected', 0, 1, 1, 0, NULL, NULL, 74, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Cancelled', 0, 1, 1, 0, NULL, NULL, 74, NULL, NULL, 1, '0000-00-00 00:00:00');
