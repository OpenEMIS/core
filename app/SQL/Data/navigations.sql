-- phpMyAdmin SQL Dump
-- version 4.0.4.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2014 at 12:57 PM
-- Server version: 5.6.11
-- PHP Version: 5.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `dev_openemis_demo`
--

-- --------------------------------------------------------

--
-- Table structure for table `navigations`
--

DROP TABLE IF EXISTS `navigations`;
CREATE TABLE IF NOT EXISTS `navigations` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL,
  `plugin` varchar(15) DEFAULT NULL,
  `controller` varchar(50) NOT NULL,
  `header` varchar(50) DEFAULT NULL,
  `title` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `pattern` varchar(200) NOT NULL,
  `attributes` varchar(50) DEFAULT NULL,
  `parent` int(3) NOT NULL,
  `is_wizard` int(1) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Dumping data for table `navigations`
--

INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'Institution', NULL, 'InstitutionSites', NULL, 'List of Institutions', 'index', 'index$|advanced', NULL, -1, 0, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(2, 'Institution', NULL, 'InstitutionSites', NULL, 'Add new Institution', 'add', 'add$', NULL, 1, 0, 2, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(3, 'Institution', NULL, 'InstitutionSites', 'GENERAL', 'Overview', 'view', 'view$|^edit$|^history', NULL, -1, 0, 3, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(4, 'Institution', NULL, 'InstitutionSites', 'GENERAL', 'Shifts', 'shifts', 'shifts', NULL, 3, 0, 4, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(5, 'Institution', NULL, 'InstitutionSites', 'GENERAL', 'Attachments', 'attachments', 'attachments', NULL, 3, 0, 5, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(6, 'Institution', NULL, 'InstitutionSites', 'FINANCE', 'Bank Accounts', 'bankAccounts', 'bankAccounts', NULL, 3, 0, 16, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(7, 'Institution', NULL, 'InstitutionSites', 'GENERAL', 'More', 'additional', 'additional', NULL, 3, 0, 6, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(8, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Programmes', 'programmes', 'programmes', NULL, 3, 0, 7, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(9, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Positions', 'positions', '^positions|^positionsHistory', NULL, 3, 0, 8, 1, NULL, NULL, 2, '0000-00-00 00:00:01'),
(10, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Students', 'students', 'students', NULL, 3, 0, 9, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(11, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Staff', 'staff', 'staff', NULL, 3, 0, 10, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(12, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Classes', 'classes', 'classes', NULL, 3, 0, 11, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(13, 'Institution', NULL, 'Census', 'TOTALS', 'Verifications', 'verifications', 'verifications', NULL, 3, 0, 17, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(14, 'Institution', NULL, 'Census', 'TOTALS', 'Students', 'enrolment', 'enrolment', NULL, 3, 0, 18, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(15, 'Institution', NULL, 'Census', 'TOTALS', 'Teachers', 'teachers', 'teachers', NULL, 3, 0, 19, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(16, 'Institution', NULL, 'Census', 'TOTALS', 'Staff', 'staff', 'staff', NULL, 3, 0, 20, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(17, 'Institution', NULL, 'Census', 'TOTALS', 'Classes', 'classes', 'classes', NULL, 3, 0, 21, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(18, 'Institution', NULL, 'Census', 'TOTALS', 'Shifts', 'shifts', 'shifts', NULL, 3, 0, 22, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(19, 'Institution', NULL, 'Census', 'TOTALS', 'Graduates', 'graduates', 'graduates', NULL, 3, 0, 23, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(20, 'Institution', NULL, 'Census', 'TOTALS', 'Attendance', 'attendance', 'attendance', NULL, 3, 0, 24, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(21, 'Institution', NULL, 'Census', 'TOTALS', 'Results', 'assessments', 'assessments', NULL, 3, 0, 25, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(22, 'Institution', NULL, 'Census', 'TOTALS', 'Behaviour', 'behaviour', 'behaviour', NULL, 3, 0, 26, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(23, 'Institution', NULL, 'Census', 'TOTALS', 'Textbooks', 'textbooks', 'textbooks', NULL, 3, 0, 27, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(24, 'Institution', NULL, 'Census', 'TOTALS', 'Infrastructure', 'infrastructure', 'infrastructure', NULL, 3, 0, 28, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(25, 'Institution', NULL, 'Census', 'TOTALS', 'Finances', 'finances', 'finances', NULL, 3, 0, 29, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(26, 'Institution', NULL, 'Census', 'TOTALS', 'More', 'otherforms', 'otherforms', NULL, 3, 0, 30, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(27, 'Institution', NULL, 'Quality', 'QUALITY', 'Rubrics', 'qualityRubric', 'qualityRubric', NULL, 3, 0, 31, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(28, 'Institution', NULL, 'Quality', 'QUALITY', 'Visits', 'qualityVisit', 'qualityVisit', NULL, 3, 0, 32, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(29, 'Institution', NULL, 'InstitutionReports', 'REPORTS', 'General', 'general', 'general', NULL, 3, 0, 34, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(30, 'Institution', NULL, 'InstitutionReports', 'REPORTS', 'Details', 'details', 'details', NULL, 3, 0, 35, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(31, 'Institution', NULL, 'InstitutionReports', 'REPORTS', 'Totals', 'totals', 'totals', NULL, 3, 0, 36, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(32, 'Institution', NULL, 'InstitutionReports', 'REPORTS', 'Quality', 'quality', 'quality', NULL, 3, 0, 37, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(33, 'Administration', NULL, 'Areas', 'SYSTEM SETUP', 'Administrative Boundaries', 'index', 'index$|levels|edit|EducationArea|$', NULL, -1, 0, 38, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(34, 'Administration', NULL, 'Education', 'SYSTEM SETUP', 'Education Structure', 'index', 'index$|systems|levels|cycles|programmes|grades|subjects|certifications|orientations|fields|reorder', NULL, 33, 0, 39, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(35, 'Administration', NULL, 'Assessment', 'SYSTEM SETUP', 'National Assessments', 'index', '^index|assessment', NULL, 33, 0, 40, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(36, 'Administration', NULL, 'FieldOption', 'SYSTEM SETUP', 'Field Options', 'index', 'index|view|edit|add', NULL, 33, 0, 41, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(37, 'Administration', NULL, 'Config', 'SYSTEM SETUP', 'System Configurations', 'index', 'index$|edit$|^dashboard|view$', NULL, 33, 0, 43, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(38, 'Administration', NULL, 'Security', 'ACCOUNTS &amp; SECURITY', 'Users', 'users', 'users', NULL, 33, 0, 44, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(39, 'Administration', NULL, 'Security', 'ACCOUNTS &amp; SECURITY', 'Groups', 'groups', '^group', NULL, 33, 0, 45, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(40, 'Administration', NULL, 'Security', 'ACCOUNTS &amp; SECURITY', 'Roles', 'roles', '^role|^permissions', NULL, 33, 0, 46, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(41, 'Administration', NULL, 'Population', 'NATIONAL DENOMINATORS', 'Population', 'index', 'index$|edit$', NULL, 33, 0, 47, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(42, 'Administration', NULL, 'Finance', 'NATIONAL DENOMINATORS', 'Finance', 'index', 'index$|edit$|financePerEducationLevel$', NULL, 33, 0, 48, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(43, 'Administration', 'DataProcessing', 'DataProcessing', 'DATA PROCESSING', 'Build', 'build', 'build', NULL, 33, 0, 49, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(44, 'Administration', 'DataProcessing', 'DataProcessing', 'DATA PROCESSING', 'Generate', 'genReports', '^gen', NULL, 33, 0, 50, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(45, 'Administration', 'DataProcessing', 'DataProcessing', 'DATA PROCESSING', 'Export', 'export', 'export', NULL, 33, 0, 51, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(46, 'Administration', 'DataProcessing', 'DataProcessing', 'DATA PROCESSING', 'Processes', 'processes', 'processes', NULL, 33, 0, 52, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(47, 'Administration', 'Database', 'Database', 'DATABASE', 'Backup', 'backup', 'backup', NULL, 33, 0, 53, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(48, 'Administration', 'Database', 'Database', 'DATABASE', 'Restore', 'restore', 'restore', NULL, 33, 0, 54, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(49, 'Administration', 'Survey', 'Survey', 'SURVEY', 'New', 'index', 'index$|^add$|^edit$', NULL, 33, 0, 55, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(50, 'Administration', 'Survey', 'Survey', 'SURVEY', 'Completed', 'import', 'import$|^synced$', NULL, 33, 0, 56, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(51, 'Administration', 'Sms', 'Sms', 'SMS', 'Messages', 'messages', 'messages', NULL, 33, 0, 57, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(52, 'Administration', 'Sms', 'Sms', 'SMS', 'Responses', 'responses', 'responses', NULL, 33, 0, 58, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(53, 'Administration', 'Sms', 'Sms', 'SMS', 'Logs', 'logs', 'logs', NULL, 33, 0, 59, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(54, 'Administration', 'Sms', 'Sms', 'SMS', 'Reports', 'reports', 'reports', NULL, 33, 0, 60, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(55, 'Administration', 'Training', 'Training', 'TRAINING', 'Courses', 'course', 'course', NULL, 33, 0, 61, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(56, 'Administration', 'Training', 'Training', 'TRAINING', 'Sessions', 'session', 'session', NULL, 33, 0, 62, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(57, 'Administration', 'Training', 'Training', 'TRAINING', 'Results', 'result', 'result', NULL, 33, 0, 63, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(58, 'Administration', NULL, 'Quality', 'QUALITY', 'Rubrics', 'rubricsTemplates', 'rubricsTemplates', NULL, 33, 0, 64, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(59, 'Administration', NULL, 'Quality', 'QUALITY', 'Status', 'status', 'status', NULL, 33, 0, 65, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(60, 'Student', 'Students', 'Students', NULL, 'List of Students', 'index', 'index$|advanced', NULL, -1, 0, 66, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(61, 'Student', 'Students', 'Students', NULL, 'Add new Student', 'add', 'add$', NULL, 60, 0, 67, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(62, 'Student', 'Students', 'Students', 'GENERAL', 'Overview', 'view', 'view$|^edit$|\\bhistory\\b', NULL, -1, 1, 68, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(63, 'Student', 'Students', 'Students', 'GENERAL', 'Contacts', 'contacts', 'contacts', NULL, 62, 1, 69, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(64, 'Student', 'Students', 'Students', 'GENERAL', 'Identities', 'identities', 'identities', NULL, 62, 1, 70, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(65, 'Student', 'Students', 'Students', 'GENERAL', 'Nationalities', 'nationalities', 'nationalities', NULL, 62, 1, 71, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(66, 'Student', 'Students', 'Students', 'GENERAL', 'Languages', 'languages', 'languages', NULL, 62, 1, 72, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(67, 'Student', 'Students', 'Students', 'FINANCE', 'Bank Accounts', 'bankAccounts', 'bankAccounts', NULL, 62, 1, 73, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(68, 'Student', 'Students', 'Students', 'GENERAL', 'Comments', 'comments', 'comments', NULL, 62, 1, 74, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(69, 'Student', 'Students', 'Students', 'GENERAL', 'Special Needs', 'specialNeed', '^specialNeed', NULL, 62, 1, 75, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(70, 'Student', 'Students', 'Students', 'GENERAL', 'Awards', 'award', '^award', NULL, 62, 1, 76, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(71, 'Student', 'Students', 'Students', 'GENERAL', 'Attachments', 'attachments', 'attachments', NULL, 62, 1, 77, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(72, 'Student', 'Students', 'Students', 'GENERAL', 'More', 'additional', 'additional|^custFieldYrView$', NULL, 62, 1, 78, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(73, 'Student', 'Students', 'Students', 'DETAILS', 'Guardians', 'guardians', 'guardians', NULL, 62, 0, 79, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(74, 'Student', 'Students', 'Students', 'DETAILS', 'Classes', 'classes', 'classes', NULL, 62, 0, 80, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(75, 'Student', 'Students', 'Students', 'DETAILS', 'Absence', 'absence', 'absence', NULL, 62, 0, 81, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(76, 'Student', 'Students', 'Students', 'DETAILS', 'Behaviour', 'behaviour', 'behaviour|^behaviourView$', NULL, 62, 0, 82, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(77, 'Student', 'Students', 'Students', 'DETAILS', 'Results', 'assessments', 'assessments', NULL, 62, 0, 83, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(78, 'Student', 'Students', 'Students', 'DETAILS', 'Extracurricular', 'extracurricular', 'extracurricular', NULL, 62, 0, 84, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(79, 'Student', 'Students', 'Students', 'HEALTH', 'Overview', 'healthView', 'healthView|healthEdit', NULL, 62, 0, 85, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(80, 'Student', 'Students', 'Students', 'HEALTH', 'History', 'healthHistory', '^healthHistory', NULL, 62, 0, 86, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(81, 'Student', 'Students', 'Students', 'HEALTH', 'Family', 'healthFamily', '^healthFamily', NULL, 62, 0, 87, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(82, 'Student', 'Students', 'Students', 'HEALTH', 'Immunizations', 'healthImmunization', '^healthImmunization', NULL, 62, 0, 88, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(83, 'Student', 'Students', 'Students', 'HEALTH', 'Medications', 'healthMedication', '^healthMedication', NULL, 62, 0, 89, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(84, 'Student', 'Students', 'Students', 'HEALTH', 'Allergies', 'healthAllergy', '^healthAllergy', NULL, 62, 0, 90, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(85, 'Student', 'Students', 'Students', 'HEALTH', 'Tests', 'healthTest', '^healthTest', NULL, 62, 0, 91, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(86, 'Student', 'Students', 'Students', 'HEALTH', 'Consultations', 'healthConsultation', '^healthConsultation', NULL, 62, 0, 92, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(87, 'Staff', 'Staff', 'Staff', NULL, 'List of Staff', 'index', 'index$|advanced', NULL, -1, 0, 93, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(88, 'Staff', 'Staff', 'Staff', NULL, 'Add new Staff', 'add', 'add$', NULL, 87, 0, 94, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(89, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Overview', 'view', 'view$|^edit$|\\bhistory\\b', NULL, -1, 1, 95, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(90, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Contacts', 'contacts', 'contacts', NULL, 89, 1, 96, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(91, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Identities', 'identities', 'identities', NULL, 89, 1, 97, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(92, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Nationalities', 'nationalities', 'nationalities', NULL, 89, 1, 98, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(93, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Languages', 'languages', 'languages', NULL, 89, 1, 99, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(94, 'Staff', 'Staff', 'Staff', 'FINANCE', 'Bank Accounts', 'bankAccounts', 'bankAccounts', NULL, 89, 1, 100, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(95, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Comments', 'comments', 'comments', NULL, 89, 1, 101, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(96, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Special Needs', 'specialNeed', '^specialNeed', NULL, 89, 1, 102, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(97, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Awards', 'award', '^award', NULL, 89, 1, 103, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(98, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Memberships', 'membership', '^membership', NULL, 89, 1, 104, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(99, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Licenses', 'license', '^license', NULL, 89, 1, 105, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(100, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Attachments', 'attachments', 'attachments', NULL, 89, 1, 106, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(101, 'Staff', 'Staff', 'Staff', 'GENERAL', 'More', 'additional', 'additional|^custFieldYrView$', NULL, 89, 1, 107, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(102, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Qualifications', 'qualifications', 'qualifications', NULL, 89, 0, 108, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(103, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Training', 'training', 'training$|trainingAdd$|trainingEdit$|trainingView$', NULL, 89, 0, 109, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(104, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Positions', 'positions', 'positions', NULL, 89, 0, 110, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(105, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Absence', 'absence', 'absence', NULL, 89, 0, 111, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(106, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Leave', 'leaves', 'leaves', NULL, 89, 0, 112, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(107, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Behaviour', 'behaviour', 'behaviour|^behaviourView$', NULL, 89, 0, 113, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(108, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Extracurricular', 'extracurricular', 'extracurricular', NULL, 89, 0, 114, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(109, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Employment', 'employments', 'employments', NULL, 89, 0, 115, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(110, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Salary', 'salaries', 'salaries', NULL, 89, 0, 116, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(111, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Overview', 'healthView', 'healthView|healthEdit', NULL, 89, 0, 117, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(112, 'Staff', 'Staff', 'Staff', 'HEALTH', 'History', 'healthHistory', '^healthHistory', NULL, 89, 0, 118, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(113, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Family', 'healthFamily', '^healthFamily', NULL, 89, 0, 119, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(114, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Immunizations', 'healthImmunization', '^healthImmunization', NULL, 89, 0, 120, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(115, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Medications', 'healthMedication', '^healthMedication', NULL, 89, 0, 121, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(116, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Allergies', 'healthAllergy', '^healthAllergy', NULL, 89, 0, 122, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(117, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Tests', 'healthTest', '^healthTest', NULL, 89, 0, 123, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(118, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Consultations', 'healthConsultation', '^healthConsultation', NULL, 89, 0, 124, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(119, 'Staff', 'Staff', 'Staff', 'TRAINING', 'Needs', 'trainingNeed', '^trainingNeed', NULL, 89, 0, 125, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(120, 'Staff', 'Staff', 'Staff', 'TRAINING', 'Results', 'trainingResult', '^trainingResult', NULL, 89, 0, 126, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(121, 'Staff', 'Staff', 'Staff', 'TRAINING', 'Achievements', 'trainingSelfStudy', '^trainingSelfStudy', NULL, 89, 0, 127, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(122, 'Staff', 'Staff', 'Staff', 'REPORT', 'Quality', 'report', 'report|reportGen', NULL, 89, 0, 128, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(123, 'Report', 'Reports', 'Reports', 'REPORTS', 'Institution Reports', 'Institution', 'Institution', NULL, -1, 0, 129, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(124, 'Report', 'Reports', 'Reports', 'REPORTS', 'Student Reports', 'Student', 'Student', NULL, 123, 0, 130, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(125, 'Report', 'Reports', 'Reports', 'REPORTS', 'Teacher Reports', 'Teacher', 'Teacher', NULL, 123, 0, 131, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(126, 'Report', 'Reports', 'Reports', 'REPORTS', 'Staff Reports', 'Staff', 'Staff', NULL, 123, 0, 132, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(127, 'Report', 'Reports', 'Reports', 'REPORTS', 'Training Reports', 'Training', 'Training', NULL, 123, 0, 133, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(128, 'Report', 'Reports', 'Reports', 'REPORTS', 'Quality Assurance Reports', 'QualityAssurance', 'QualityAssurance', NULL, 123, 0, 134, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(129, 'Report', 'Reports', 'Reports', 'REPORTS', 'Consolidated Reports', 'Consolidated', 'Consolidated', NULL, 123, 0, 135, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(130, 'Report', 'Reports', 'Reports', 'REPORTS', 'Data Quality Reports', 'DataQuality', 'DataQuality', NULL, 123, 0, 136, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(131, 'Report', 'Reports', 'Reports', 'REPORTS', 'Indicator Reports', 'Indicator', 'Indicator', NULL, 123, 0, 137, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(132, 'Report', NULL, 'Report', 'REPORTS', 'Custom Reports', 'index', 'index|^reports', NULL, 123, 0, 138, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(133, 'Home', NULL, 'Home', NULL, 'My Details', 'details', 'details', NULL, -1, 0, 140, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(134, 'Home', NULL, 'Home', NULL, 'Change Password', 'password', 'password', NULL, 133, 0, 141, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(135, 'Home', NULL, 'Home', NULL, 'Contact', 'support', 'support', NULL, -1, 0, 142, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(136, 'Home', NULL, 'Home', NULL, 'System Information', 'systemInfo', 'systemInfo', NULL, 135, 0, 143, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(137, 'Home', NULL, 'Home', NULL, 'License', 'license', 'license', NULL, 135, 0, 144, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(138, 'Home', NULL, 'Home', NULL, 'Partners', 'partners', 'partners', NULL, 135, 0, 145, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(139, 'Institution', NULL, 'InstitutionSites', 'ATTENDANCE', 'Students', 'attendanceStudent', 'attendanceStudent', NULL, 3, 0, 12, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(140, 'Institution', NULL, 'InstitutionSites', 'ATTENDANCE', 'Staff', 'attendanceStaff', 'attendanceStaff', NULL, 3, 0, 13, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(141, 'Report', 'Dashboards', 'Dashboards', 'REPORTS', 'Dashboards', 'dashboardReport', 'dashboardReport', NULL, 123, 0, 139, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(142, 'Institution', 'Dashboards', 'Dashboards', 'REPORTS', 'Dashboards', 'general', 'general', NULL, 3, 0, 33, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(143, 'Institution', NULL, 'InstitutionSites', 'BEHAVIOURS', 'Students', 'behaviourStudentList', 'behaviourStudentList', NULL, 3, 0, 14, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(144, 'Institution', NULL, 'InstitutionSites', 'BEHAVIOURS', 'Staff', 'behaviourStaffList', 'behaviourStaffList', NULL, 3, 0, 15, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(145, 'Administration', NULL, 'Translations', 'SYSTEM SETUP', 'Translations', 'index', 'index|view|edit|add', NULL, 33, 0, 42, 1, NULL, NULL, 1, '0000-00-00 00:00:00');
