-- phpMyAdmin SQL Dump
-- version 4.0.4.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2014 at 04:30 PM
-- Server version: 5.6.11
-- PHP Version: 5.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

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
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=178 ;

--
-- Truncate table before insert `navigations`
--

TRUNCATE TABLE `navigations`;
--
-- Dumping data for table `navigations`
--

INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'Institution', NULL, 'Institutions', NULL, 'List of Institutions', 'index', 'index$|advanced', NULL, -1, 0, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(2, 'Institution', NULL, 'Institutions', NULL, 'Add new Institution', 'add', 'add$', NULL, 1, 0, 2, NULL, NULL, 1, '0000-00-00 00:00:00'),
(3, 'Institution', NULL, 'Institutions', 'GENERAL', 'Overview', 'view', 'view$|^edit$|history$', NULL, -1, 0, 3, NULL, NULL, 1, '0000-00-00 00:00:00'),
(4, 'Institution', NULL, 'Institutions', 'GENERAL', 'Attachments', 'attachments', 'attachments', NULL, 3, 0, 4, NULL, NULL, 1, '0000-00-00 00:00:00'),
(5, 'Institution', NULL, 'Institutions', 'GENERAL', 'More', 'additional', 'additional', NULL, 3, 0, 5, NULL, NULL, 1, '0000-00-00 00:00:00'),
(6, 'Institution', NULL, 'Institutions', 'INSTITUTION SITE', 'List of Institution Sites', 'listSites', 'listSites$', NULL, 3, 0, 6, NULL, NULL, 1, '0000-00-00 00:00:00'),
(7, 'Institution', NULL, 'InstitutionSites', 'INSTITUTION SITE', 'Add new Institution Site', 'add', 'add$', NULL, 3, 0, 7, NULL, NULL, 1, '0000-00-00 00:00:00'),
(8, 'Institution', NULL, 'InstitutionSites', 'GENERAL', 'Overview', 'view', 'view$|^edit$|history$', NULL, -1, 0, 8, NULL, NULL, 1, '0000-00-00 00:00:00'),
(9, 'Institution', NULL, 'InstitutionSites', 'GENERAL', 'Shifts', 'shifts', 'shifts', NULL, 8, 0, 9, NULL, NULL, 1, '0000-00-00 00:00:00'),
(10, 'Institution', NULL, 'InstitutionSites', 'GENERAL', 'Attachments', 'attachments', 'attachments', NULL, 8, 0, 10, NULL, NULL, 1, '0000-00-00 00:00:00'),
(11, 'Institution', NULL, 'InstitutionSites', 'GENERAL', 'Bank Accounts', 'bankAccounts', 'bankAccounts', NULL, 8, 0, 11, NULL, NULL, 1, '0000-00-00 00:00:00'),
(12, 'Institution', NULL, 'InstitutionSites', 'GENERAL', 'More', 'additional', 'additional', NULL, 8, 0, 12, NULL, NULL, 1, '0000-00-00 00:00:00'),
(13, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Programmes', 'programmes', 'programmes', NULL, 8, 0, 13, NULL, NULL, 1, '0000-00-00 00:00:00'),
(14, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Students', 'students', 'students', NULL, 8, 0, 14, NULL, NULL, 1, '0000-00-00 00:00:00'),
(15, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Teachers', 'teachers', 'teachers', NULL, 8, 0, 15, NULL, NULL, 1, '0000-00-00 00:00:00'),
(16, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Staff', 'staff', 'staff', NULL, 8, 0, 16, NULL, NULL, 1, '0000-00-00 00:00:00'),
(17, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Classes', 'classes', 'classes', NULL, 8, 0, 17, NULL, NULL, 1, '0000-00-00 00:00:00'),
(18, 'Institution', NULL, 'Census', 'TOTALS', 'Verifications', 'verifications', 'verifications', NULL, 8, 0, 18, NULL, NULL, 1, '0000-00-00 00:00:00'),
(19, 'Institution', NULL, 'Census', 'TOTALS', 'Students', 'enrolment', 'enrolment', NULL, 8, 0, 19, NULL, NULL, 1, '0000-00-00 00:00:00'),
(20, 'Institution', NULL, 'Census', 'TOTALS', 'Teachers', 'teachers', 'teachers', NULL, 8, 0, 20, NULL, NULL, 1, '0000-00-00 00:00:00'),
(21, 'Institution', NULL, 'Census', 'TOTALS', 'Staff', 'staff', 'staff', NULL, 8, 0, 21, NULL, NULL, 1, '0000-00-00 00:00:00'),
(22, 'Institution', NULL, 'Census', 'TOTALS', 'Classes', 'classes', 'classes', NULL, 8, 0, 22, NULL, NULL, 1, '0000-00-00 00:00:00'),
(23, 'Institution', NULL, 'Census', 'TOTALS', 'Shifts', 'shifts', 'shifts', NULL, 8, 0, 23, NULL, NULL, 1, '0000-00-00 00:00:00'),
(24, 'Institution', NULL, 'Census', 'TOTALS', 'Graduates', 'graduates', 'graduates', NULL, 8, 0, 24, NULL, NULL, 1, '0000-00-00 00:00:00'),
(25, 'Institution', NULL, 'Census', 'TOTALS', 'Attendance', 'attendance', 'attendance', NULL, 8, 0, 25, NULL, NULL, 1, '0000-00-00 00:00:00'),
(26, 'Institution', NULL, 'Census', 'TOTALS', 'Results', 'assessments', 'assessments', NULL, 8, 0, 26, NULL, NULL, 1, '0000-00-00 00:00:00'),
(27, 'Institution', NULL, 'Census', 'TOTALS', 'Behaviour', 'behaviour', 'behaviour', NULL, 8, 0, 27, NULL, NULL, 1, '0000-00-00 00:00:00'),
(28, 'Institution', NULL, 'Census', 'TOTALS', 'Textbooks', 'textbooks', 'textbooks', NULL, 8, 0, 28, NULL, NULL, 1, '0000-00-00 00:00:00'),
(29, 'Institution', NULL, 'Census', 'TOTALS', 'Infrastructure', 'infrastructure', 'infrastructure', NULL, 8, 0, 29, NULL, NULL, 1, '0000-00-00 00:00:00'),
(30, 'Institution', NULL, 'Census', 'TOTALS', 'Finances', 'finances', 'finances', NULL, 8, 0, 30, NULL, NULL, 1, '0000-00-00 00:00:00'),
(31, 'Institution', NULL, 'Census', 'TOTALS', 'More', 'otherforms', 'otherforms', NULL, 8, 0, 31, NULL, NULL, 1, '0000-00-00 00:00:00'),
(32, 'Institution', NULL, 'Quality', 'QUALITY', 'Rubrics', 'qualityRubric', 'qualityRubric', NULL, 8, 0, 32, NULL, NULL, 1, '0000-00-00 00:00:00'),
(33, 'Institution', NULL, 'Quality', 'QUALITY', 'Visits', 'qualityVisit', 'qualityVisit', NULL, 8, 0, 33, NULL, NULL, 1, '0000-00-00 00:00:00'),
(34, 'Institution', NULL, 'InstitutionSites', 'REPORTS', 'General', 'reportsGeneral', 'reportsGeneral', NULL, 8, 0, 34, NULL, NULL, 1, '0000-00-00 00:00:00'),
(35, 'Institution', NULL, 'InstitutionSites', 'REPORTS', 'Details', 'reportsDetails', 'reportsDetails', NULL, 8, 0, 35, NULL, NULL, 1, '0000-00-00 00:00:00'),
(36, 'Institution', NULL, 'InstitutionSites', 'REPORTS', 'Totals', 'reportsTotals', 'reportsTotals', NULL, 8, 0, 36, NULL, NULL, 1, '0000-00-00 00:00:00'),
(37, 'Institution', NULL, 'InstitutionSites', 'REPORTS', 'Quality', 'reportsQuality', 'reportsQuality', NULL, 8, 0, 37, NULL, NULL, 1, '0000-00-00 00:00:00'),
(38, 'Administration', NULL, 'Areas', 'SYSTEM SETUP', 'Administrative Boundaries', 'index', 'index$|levels|edit|EducationArea|$', NULL, -1, 0, 38, NULL, NULL, 1, '0000-00-00 00:00:00'),
(39, 'Administration', NULL, 'Education', 'SYSTEM SETUP', 'Education Structure', 'index', 'index$|setup', NULL, 38, 0, 39, NULL, NULL, 1, '0000-00-00 00:00:00'),
(40, 'Administration', NULL, 'Assessment', 'SYSTEM SETUP', 'National Assessments', 'index', '^index|assessment', NULL, 38, 0, 40, NULL, NULL, 1, '0000-00-00 00:00:00'),
(41, 'Administration', NULL, 'FieldOption', 'SYSTEM SETUP', 'Field Options', 'index', 'index|view|edit|add', NULL, 38, 0, 41, NULL, NULL, 1, '0000-00-00 00:00:00'),
(42, 'Administration', NULL, 'Config', 'SYSTEM SETUP', 'System Configurations', 'index', 'index$|edit$|^dashboard', NULL, 38, 0, 42, NULL, NULL, 1, '0000-00-00 00:00:00'),
(43, 'Administration', NULL, 'Security', 'ACCOUNTS &amp; SECURITY', 'Users', 'users', 'users', NULL, 38, 0, 43, NULL, NULL, 1, '0000-00-00 00:00:00'),
(44, 'Administration', NULL, 'Security', 'ACCOUNTS &amp; SECURITY', 'Groups', 'groups', '^group', NULL, 38, 0, 44, NULL, NULL, 1, '0000-00-00 00:00:00'),
(45, 'Administration', NULL, 'Security', 'ACCOUNTS &amp; SECURITY', 'Roles', 'roles', '^role|^permissions', NULL, 38, 0, 45, NULL, NULL, 1, '0000-00-00 00:00:00'),
(46, 'Administration', NULL, 'Population', 'NATIONAL DENOMINATORS', 'Population', 'index', 'index$|edit$', NULL, 38, 0, 46, NULL, NULL, 1, '0000-00-00 00:00:00'),
(47, 'Administration', NULL, 'Finance', 'NATIONAL DENOMINATORS', 'Finance', 'index', 'index$|edit$|financePerEducationLevel$', NULL, 38, 0, 47, NULL, NULL, 1, '0000-00-00 00:00:00'),
(48, 'Administration', 'DataProcessing', 'DataProcessing', 'DATA PROCESSING', 'Build', 'build', 'build', NULL, 38, 0, 48, NULL, NULL, 1, '0000-00-00 00:00:00'),
(49, 'Administration', 'DataProcessing', 'DataProcessing', 'DATA PROCESSING', 'Generate', 'genReports', '^gen', NULL, 38, 0, 49, NULL, NULL, 1, '0000-00-00 00:00:00'),
(50, 'Administration', 'DataProcessing', 'DataProcessing', 'DATA PROCESSING', 'Export', 'export', 'export', NULL, 38, 0, 50, NULL, NULL, 1, '0000-00-00 00:00:00'),
(51, 'Administration', 'DataProcessing', 'DataProcessing', 'DATA PROCESSING', 'Processes', 'processes', 'processes', NULL, 38, 0, 51, NULL, NULL, 1, '0000-00-00 00:00:00'),
(52, 'Administration', 'Database', 'Database', 'DATABASE', 'Backup', 'backup', 'backup', NULL, 38, 0, 52, NULL, NULL, 1, '0000-00-00 00:00:00'),
(53, 'Administration', 'Database', 'Database', 'DATABASE', 'Restore', 'restore', 'restore', NULL, 38, 0, 53, NULL, NULL, 1, '0000-00-00 00:00:00'),
(54, 'Administration', 'Survey', 'Survey', 'SURVEY', 'New', 'index', 'index$|^add$|^edit$', NULL, 38, 0, 54, NULL, NULL, 1, '0000-00-00 00:00:00'),
(55, 'Administration', 'Survey', 'Survey', 'SURVEY', 'Completed', 'import', 'import$|^synced$', NULL, 38, 0, 55, NULL, NULL, 1, '0000-00-00 00:00:00'),
(56, 'Administration', 'Sms', 'Sms', 'SMS', 'Messages', 'messages', 'messages', NULL, 38, 0, 56, NULL, NULL, 1, '0000-00-00 00:00:00'),
(57, 'Administration', 'Sms', 'Sms', 'SMS', 'Responses', 'responses', 'responses', NULL, 38, 0, 57, NULL, NULL, 1, '0000-00-00 00:00:00'),
(58, 'Administration', 'Sms', 'Sms', 'SMS', 'Logs', 'logs', 'logs', NULL, 38, 0, 58, NULL, NULL, 1, '0000-00-00 00:00:00'),
(59, 'Administration', 'Sms', 'Sms', 'SMS', 'Reports', 'reports', 'reports', NULL, 38, 0, 59, NULL, NULL, 1, '0000-00-00 00:00:00'),
(60, 'Administration', 'Training', 'Training', 'TRAINING', 'Courses', 'course', 'course', NULL, 38, 0, 60, NULL, NULL, 1, '0000-00-00 00:00:00'),
(61, 'Administration', 'Training', 'Training', 'TRAINING', 'Sessions', 'session', 'session', NULL, 38, 0, 61, NULL, NULL, 1, '0000-00-00 00:00:00'),
(62, 'Administration', 'Training', 'Training', 'TRAINING', 'Results', 'result', 'result', NULL, 38, 0, 62, NULL, NULL, 1, '0000-00-00 00:00:00'),
(63, 'Administration', NULL, 'Quality', 'QUALITY', 'Rubrics', 'rubricsTemplates', 'rubricsTemplates', NULL, 38, 0, 63, NULL, NULL, 1, '0000-00-00 00:00:00'),
(64, 'Administration', NULL, 'Quality', 'QUALITY', 'Status', 'status', 'status', NULL, 38, 0, 64, NULL, NULL, 1, '0000-00-00 00:00:00'),
(65, 'Student', 'Students', 'Students', NULL, 'List of Students', 'index', 'index$|advanced', NULL, -1, 0, 65, NULL, NULL, 1, '0000-00-00 00:00:00'),
(66, 'Student', 'Students', 'Students', NULL, 'Add new Student', 'add', 'add$', NULL, 65, 0, 66, NULL, NULL, 1, '0000-00-00 00:00:00'),
(67, 'Student', 'Students', 'Students', 'GENERAL', 'Overview', 'view', 'view$|^edit$|\\bhistory\\b', NULL, -1, 1, 67, NULL, NULL, 1, '0000-00-00 00:00:00'),
(68, 'Student', 'Students', 'Students', 'GENERAL', 'Contacts', 'contacts', 'contacts', NULL, 67, 1, 68, NULL, NULL, 1, '0000-00-00 00:00:00'),
(69, 'Student', 'Students', 'Students', 'GENERAL', 'Identities', 'identities', 'identities', NULL, 67, 1, 69, NULL, NULL, 1, '0000-00-00 00:00:00'),
(70, 'Student', 'Students', 'Students', 'GENERAL', 'Nationalities', 'nationalities', 'nationalities', NULL, 67, 1, 70, NULL, NULL, 1, '0000-00-00 00:00:00'),
(71, 'Student', 'Students', 'Students', 'GENERAL', 'Languages', 'languages', 'languages', NULL, 67, 1, 71, NULL, NULL, 1, '0000-00-00 00:00:00'),
(72, 'Student', 'Students', 'Students', 'GENERAL', 'Bank Accounts', 'bankAccounts', 'bankAccounts', NULL, 67, 1, 72, NULL, NULL, 1, '0000-00-00 00:00:00'),
(73, 'Student', 'Students', 'Students', 'GENERAL', 'Comments', 'comments', 'comments', NULL, 67, 1, 73, NULL, NULL, 1, '0000-00-00 00:00:00'),
(74, 'Student', 'Students', 'Students', 'GENERAL', 'Special Needs', 'specialNeed', '^specialNeed', NULL, 67, 1, 74, NULL, NULL, 1, '0000-00-00 00:00:00'),
(75, 'Student', 'Students', 'Students', 'GENERAL', 'Awards', 'award', '^award', NULL, 67, 1, 75, NULL, NULL, 1, '0000-00-00 00:00:00'),
(76, 'Student', 'Students', 'Students', 'GENERAL', 'Attachments', 'attachments', 'attachments', NULL, 67, 1, 76, NULL, NULL, 1, '0000-00-00 00:00:00'),
(77, 'Student', 'Students', 'Students', 'GENERAL', 'More', 'additional', 'additional|^custFieldYrView$', NULL, 67, 1, 77, NULL, NULL, 1, '0000-00-00 00:00:00'),
(78, 'Student', 'Students', 'Students', 'DETAILS', 'Guardians', 'guardians', 'guardians', NULL, 67, 0, 78, NULL, NULL, 1, '0000-00-00 00:00:00'),
(79, 'Student', 'Students', 'Students', 'DETAILS', 'Classes', 'classes', 'classes', NULL, 67, 0, 79, NULL, NULL, 1, '0000-00-00 00:00:00'),
(80, 'Student', 'Students', 'Students', 'DETAILS', 'Attendance', 'attendance', 'attendance', NULL, 67, 0, 80, NULL, NULL, 1, '0000-00-00 00:00:00'),
(81, 'Student', 'Students', 'Students', 'DETAILS', 'Behaviour', 'behaviour', 'behaviour|^behaviourView$', NULL, 67, 0, 81, NULL, NULL, 1, '0000-00-00 00:00:00'),
(82, 'Student', 'Students', 'Students', 'DETAILS', 'Results', 'assessments', 'assessments', NULL, 67, 0, 82, NULL, NULL, 1, '0000-00-00 00:00:00'),
(83, 'Student', 'Students', 'Students', 'DETAILS', 'Extracurricular', 'extracurricular', 'extracurricular', NULL, 67, 0, 83, NULL, NULL, 1, '0000-00-00 00:00:00'),
(84, 'Student', 'Students', 'Students', 'HEALTH', 'Overview', 'healthView', 'healthView|healthEdit', NULL, 67, 0, 84, NULL, NULL, 1, '0000-00-00 00:00:00'),
(85, 'Student', 'Students', 'Students', 'HEALTH', 'History', 'healthHistory', '^healthHistory', NULL, 67, 0, 85, NULL, NULL, 1, '0000-00-00 00:00:00'),
(86, 'Student', 'Students', 'Students', 'HEALTH', 'Family', 'healthFamily', '^healthFamily', NULL, 67, 0, 86, NULL, NULL, 1, '0000-00-00 00:00:00'),
(87, 'Student', 'Students', 'Students', 'HEALTH', 'Immunizations', 'healthImmunization', '^healthImmunization', NULL, 67, 0, 87, NULL, NULL, 1, '0000-00-00 00:00:00'),
(88, 'Student', 'Students', 'Students', 'HEALTH', 'Medications', 'healthMedication', '^healthMedication', NULL, 67, 0, 88, NULL, NULL, 1, '0000-00-00 00:00:00'),
(89, 'Student', 'Students', 'Students', 'HEALTH', 'Allergies', 'healthAllergy', '^healthAllergy', NULL, 67, 0, 89, NULL, NULL, 1, '0000-00-00 00:00:00'),
(90, 'Student', 'Students', 'Students', 'HEALTH', 'Tests', 'healthTest', '^healthTest', NULL, 67, 0, 90, NULL, NULL, 1, '0000-00-00 00:00:00'),
(91, 'Student', 'Students', 'Students', 'HEALTH', 'Consultations', 'healthConsultation', '^healthConsultation', NULL, 67, 0, 91, NULL, NULL, 1, '0000-00-00 00:00:00'),
(92, 'Teacher', 'Teachers', 'Teachers', NULL, 'List of Teachers', 'index', 'index$|advanced', NULL, -1, 0, 92, NULL, NULL, 1, '0000-00-00 00:00:00'),
(93, 'Teacher', 'Teachers', 'Teachers', NULL, 'Add new Teacher', 'add', 'add$', NULL, 92, 0, 93, NULL, NULL, 1, '0000-00-00 00:00:00'),
(94, 'Teacher', 'Teachers', 'Teachers', 'GENERAL', 'Overview', 'view', 'view$|^edit$|\\bhistory\\b', NULL, -1, 1, 94, NULL, NULL, 1, '0000-00-00 00:00:00'),
(95, 'Teacher', 'Teachers', 'Teachers', 'GENERAL', 'Contacts', 'contacts', 'contacts', NULL, 94, 1, 95, NULL, NULL, 1, '0000-00-00 00:00:00'),
(96, 'Teacher', 'Teachers', 'Teachers', 'GENERAL', 'Identities', 'identities', 'identities', NULL, 94, 1, 96, NULL, NULL, 1, '0000-00-00 00:00:00'),
(97, 'Teacher', 'Teachers', 'Teachers', 'GENERAL', 'Nationalities', 'nationalities', 'nationalities', NULL, 94, 1, 97, NULL, NULL, 1, '0000-00-00 00:00:00'),
(98, 'Teacher', 'Teachers', 'Teachers', 'GENERAL', 'Languages', 'languages', 'languages', NULL, 94, 1, 98, NULL, NULL, 1, '0000-00-00 00:00:00'),
(99, 'Teacher', 'Teachers', 'Teachers', 'GENERAL', 'Bank Accounts', 'bankAccounts', 'bankAccounts', NULL, 94, 1, 99, NULL, NULL, 1, '0000-00-00 00:00:00'),
(100, 'Teacher', 'Teachers', 'Teachers', 'GENERAL', 'Comments', 'comments', 'comments', NULL, 94, 1, 100, NULL, NULL, 1, '0000-00-00 00:00:00'),
(101, 'Teacher', 'Teachers', 'Teachers', 'GENERAL', 'Special Needs', 'specialNeed', '^specialNeed', NULL, 94, 1, 101, NULL, NULL, 1, '0000-00-00 00:00:00'),
(102, 'Teacher', 'Teachers', 'Teachers', 'GENERAL', 'Awards', 'award', '^award', NULL, 94, 1, 102, NULL, NULL, 1, '0000-00-00 00:00:00'),
(103, 'Teacher', 'Teachers', 'Teachers', 'GENERAL', 'Memberships', 'membership', '^membership', NULL, 94, 1, 103, NULL, NULL, 1, '0000-00-00 00:00:00'),
(104, 'Teacher', 'Teachers', 'Teachers', 'GENERAL', 'Licenses', 'license', '^license', NULL, 94, 1, 104, NULL, NULL, 1, '0000-00-00 00:00:00'),
(105, 'Teacher', 'Teachers', 'Teachers', 'GENERAL', 'Attachments', 'attachments', 'attachments', NULL, 94, 1, 105, NULL, NULL, 1, '0000-00-00 00:00:00'),
(106, 'Teacher', 'Teachers', 'Teachers', 'GENERAL', 'More', 'additional', 'additional|^custFieldYrView$', NULL, 94, 1, 106, NULL, NULL, 1, '0000-00-00 00:00:00'),
(107, 'Teacher', 'Teachers', 'Teachers', 'DETAILS', 'Qualifications', 'qualifications', 'qualifications', NULL, 94, 0, 107, NULL, NULL, 1, '0000-00-00 00:00:00'),
(108, 'Teacher', 'Teachers', 'Teachers', 'DETAILS', 'Training', 'training', 'training$|trainingAdd$|trainingEdit$', NULL, 94, 0, 108, NULL, NULL, 1, '0000-00-00 00:00:00'),
(109, 'Teacher', 'Teachers', 'Teachers', 'DETAILS', 'Positions', 'positions', 'positions', NULL, 94, 0, 109, NULL, NULL, 1, '0000-00-00 00:00:00'),
(110, 'Teacher', 'Teachers', 'Teachers', 'DETAILS', 'Attendance', 'attendance', 'attendance', NULL, 94, 0, 110, NULL, NULL, 1, '0000-00-00 00:00:00'),
(111, 'Teacher', 'Teachers', 'Teachers', 'DETAILS', 'Leave', 'leaves', 'leaves', NULL, 94, 0, 111, NULL, NULL, 1, '0000-00-00 00:00:00'),
(112, 'Teacher', 'Teachers', 'Teachers', 'DETAILS', 'Behaviour', 'behaviour', 'behaviour|^behaviourView$', NULL, 94, 0, 112, NULL, NULL, 1, '0000-00-00 00:00:00'),
(113, 'Teacher', 'Teachers', 'Teachers', 'DETAILS', 'Extracurricular', 'extracurricular', 'extracurricular', NULL, 94, 0, 113, NULL, NULL, 1, '0000-00-00 00:00:00'),
(114, 'Teacher', 'Teachers', 'Teachers', 'DETAILS', 'Employment', 'employments', 'employments', NULL, 94, 0, 114, NULL, NULL, 1, '0000-00-00 00:00:00'),
(115, 'Teacher', 'Teachers', 'Teachers', 'DETAILS', 'Salary', 'salaries', 'salaries', NULL, 94, 0, 115, NULL, NULL, 1, '0000-00-00 00:00:00'),
(116, 'Teacher', 'Teachers', 'Teachers', 'HEALTH', 'Overview', 'healthView', 'healthView|healthEdit', NULL, 94, 0, 116, NULL, NULL, 1, '0000-00-00 00:00:00'),
(117, 'Teacher', 'Teachers', 'Teachers', 'HEALTH', 'History', 'healthHistory', '^healthHistory', NULL, 94, 0, 117, NULL, NULL, 1, '0000-00-00 00:00:00'),
(118, 'Teacher', 'Teachers', 'Teachers', 'HEALTH', 'Family', 'healthFamily', '^healthFamily', NULL, 94, 0, 118, NULL, NULL, 1, '0000-00-00 00:00:00'),
(119, 'Teacher', 'Teachers', 'Teachers', 'HEALTH', 'Immunizations', 'healthImmunization', '^healthImmunization', NULL, 94, 0, 119, NULL, NULL, 1, '0000-00-00 00:00:00'),
(120, 'Teacher', 'Teachers', 'Teachers', 'HEALTH', 'Medications', 'healthMedication', '^healthMedication', NULL, 94, 0, 120, NULL, NULL, 1, '0000-00-00 00:00:00'),
(121, 'Teacher', 'Teachers', 'Teachers', 'HEALTH', 'Allergies', 'healthAllergy', '^healthAllergy', NULL, 94, 0, 121, NULL, NULL, 1, '0000-00-00 00:00:00'),
(122, 'Teacher', 'Teachers', 'Teachers', 'HEALTH', 'Tests', 'healthTest', '^healthTest', NULL, 94, 0, 122, NULL, NULL, 1, '0000-00-00 00:00:00'),
(123, 'Teacher', 'Teachers', 'Teachers', 'HEALTH', 'Consultations', 'healthConsultation', '^healthConsultation', NULL, 94, 0, 123, NULL, NULL, 1, '0000-00-00 00:00:00'),
(124, 'Teacher', 'Teachers', 'Teachers', 'TRAINING', 'Needs', 'trainingNeed', '^trainingNeed', NULL, 94, 0, 124, NULL, NULL, 1, '0000-00-00 00:00:00'),
(125, 'Teacher', 'Teachers', 'Teachers', 'TRAINING', 'Results', 'trainingResult', '^trainingResult', NULL, 94, 0, 125, NULL, NULL, 1, '0000-00-00 00:00:00'),
(126, 'Teacher', 'Teachers', 'Teachers', 'TRAINING', 'Achievements', 'trainingSelfStudy', '^trainingSelfStudy', NULL, 94, 0, 126, NULL, NULL, 1, '0000-00-00 00:00:00'),
(127, 'Teacher', 'Teachers', 'Teachers', 'REPORT', 'Quality', 'report', 'report|reportGen', NULL, 94, 0, 127, NULL, NULL, 1, '0000-00-00 00:00:00'),
(128, 'Staff', 'Staff', 'Staff', NULL, 'List of Staff', 'index', 'index$|advanced', NULL, -1, 0, 128, NULL, NULL, 1, '0000-00-00 00:00:00'),
(129, 'Staff', 'Staff', 'Staff', NULL, 'Add new Staff', 'add', 'add$', NULL, 128, 0, 129, NULL, NULL, 1, '0000-00-00 00:00:00'),
(130, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Overview', 'view', 'view$|^edit$|\\bhistory\\b', NULL, -1, 1, 130, NULL, NULL, 1, '0000-00-00 00:00:00'),
(131, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Contacts', 'contacts', 'contacts', NULL, 130, 1, 131, NULL, NULL, 1, '0000-00-00 00:00:00'),
(132, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Identities', 'identities', 'identities', NULL, 130, 1, 132, NULL, NULL, 1, '0000-00-00 00:00:00'),
(133, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Nationalities', 'nationalities', 'nationalities', NULL, 130, 1, 133, NULL, NULL, 1, '0000-00-00 00:00:00'),
(134, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Languages', 'languages', 'languages', NULL, 130, 1, 134, NULL, NULL, 1, '0000-00-00 00:00:00'),
(135, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Bank Accounts', 'bankAccounts', 'bankAccounts', NULL, 130, 1, 135, NULL, NULL, 1, '0000-00-00 00:00:00'),
(136, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Comments', 'comments', 'comments', NULL, 130, 1, 136, NULL, NULL, 1, '0000-00-00 00:00:00'),
(137, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Special Needs', 'specialNeed', '^specialNeed', NULL, 130, 1, 137, NULL, NULL, 1, '0000-00-00 00:00:00'),
(138, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Awards', 'award', '^award', NULL, 130, 1, 138, NULL, NULL, 1, '0000-00-00 00:00:00'),
(139, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Memberships', 'membership', '^membership', NULL, 130, 1, 139, NULL, NULL, 1, '0000-00-00 00:00:00'),
(140, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Licenses', 'license', '^license', NULL, 130, 1, 140, NULL, NULL, 1, '0000-00-00 00:00:00'),
(141, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Attachments', 'attachments', 'attachments', NULL, 130, 1, 141, NULL, NULL, 1, '0000-00-00 00:00:00'),
(142, 'Staff', 'Staff', 'Staff', 'GENERAL', 'More', 'additional', 'additional|^custFieldYrView$', NULL, 130, 1, 142, NULL, NULL, 1, '0000-00-00 00:00:00'),
(143, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Qualifications', 'qualifications', 'qualifications', NULL, 130, 0, 143, NULL, NULL, 1, '0000-00-00 00:00:00'),
(144, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Positions', 'positions', 'positions', NULL, 130, 0, 144, NULL, NULL, 1, '0000-00-00 00:00:00'),
(145, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Attendance', 'attendance', 'attendance', NULL, 130, 0, 145, NULL, NULL, 1, '0000-00-00 00:00:00'),
(146, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Leave', 'leaves', 'leaves', NULL, 130, 0, 146, NULL, NULL, 1, '0000-00-00 00:00:00'),
(147, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Behaviour', 'behaviour', 'behaviour|^behaviourView$', NULL, 130, 0, 147, NULL, NULL, 1, '0000-00-00 00:00:00'),
(148, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Extracurricular', 'extracurricular', 'extracurricular', NULL, 130, 0, 148, NULL, NULL, 1, '0000-00-00 00:00:00'),
(149, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Employment', 'employments', 'employments', NULL, 130, 0, 149, NULL, NULL, 1, '0000-00-00 00:00:00'),
(150, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Salary', 'salaries', 'salaries', NULL, 130, 0, 150, NULL, NULL, 1, '0000-00-00 00:00:00'),
(151, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Overview', 'healthView', 'healthView|healthEdit', NULL, 130, 0, 151, NULL, NULL, 1, '0000-00-00 00:00:00'),
(152, 'Staff', 'Staff', 'Staff', 'HEALTH', 'History', 'healthHistory', '^healthHistory', NULL, 130, 0, 152, NULL, NULL, 1, '0000-00-00 00:00:00'),
(153, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Family', 'healthFamily', '^healthFamily', NULL, 130, 0, 153, NULL, NULL, 1, '0000-00-00 00:00:00'),
(154, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Immunizations', 'healthImmunization', '^healthImmunization', NULL, 130, 0, 154, NULL, NULL, 1, '0000-00-00 00:00:00'),
(155, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Medications', 'healthMedication', '^healthMedication', NULL, 130, 0, 155, NULL, NULL, 1, '0000-00-00 00:00:00'),
(156, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Allergies', 'healthAllergy', '^healthAllergy', NULL, 130, 0, 156, NULL, NULL, 1, '0000-00-00 00:00:00'),
(157, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Tests', 'healthTest', '^healthTest', NULL, 130, 0, 157, NULL, NULL, 1, '0000-00-00 00:00:00'),
(158, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Consultations', 'healthConsultation', '^healthConsultation', NULL, 130, 0, 158, NULL, NULL, 1, '0000-00-00 00:00:00'),
(159, 'Staff', 'Staff', 'Staff', 'TRAINING', 'Needs', 'trainingNeed', '^trainingNeed', NULL, 130, 0, 159, NULL, NULL, 1, '0000-00-00 00:00:00'),
(160, 'Staff', 'Staff', 'Staff', 'TRAINING', 'Results', 'trainingResult', '^trainingResult', NULL, 130, 0, 160, NULL, NULL, 1, '0000-00-00 00:00:00'),
(161, 'Staff', 'Staff', 'Staff', 'TRAINING', 'Achievements', 'trainingSelfStudy', '^trainingSelfStudy', NULL, 130, 0, 161, NULL, NULL, 1, '0000-00-00 00:00:00'),
(162, 'Report', 'Reports', 'Reports', 'REPORTS', 'Institution Reports', 'Institution', 'Institution', NULL, -1, 0, 162, NULL, NULL, 1, '0000-00-00 00:00:00'),
(163, 'Report', 'Reports', 'Reports', 'REPORTS', 'Student Reports', 'Student', 'Student', NULL, 162, 0, 163, NULL, NULL, 1, '0000-00-00 00:00:00'),
(164, 'Report', 'Reports', 'Reports', 'REPORTS', 'Teacher Reports', 'Teacher', 'Teacher', NULL, 162, 0, 164, NULL, NULL, 1, '0000-00-00 00:00:00'),
(165, 'Report', 'Reports', 'Reports', 'REPORTS', 'Staff Reports', 'Staff', 'Staff', NULL, 162, 0, 165, NULL, NULL, 1, '0000-00-00 00:00:00'),
(166, 'Report', 'Reports', 'Reports', 'REPORTS', 'Training Reports', 'Training', 'Training', NULL, 162, 0, 166, NULL, NULL, 1, '0000-00-00 00:00:00'),
(167, 'Report', 'Reports', 'Reports', 'REPORTS', 'Quality Assurance Reports', 'QualityAssurance', 'QualityAssurance', NULL, 162, 0, 167, NULL, NULL, 1, '0000-00-00 00:00:00'),
(168, 'Report', 'Reports', 'Reports', 'REPORTS', 'Consolidated Reports', 'Consolidated', 'Consolidated', NULL, 162, 0, 168, NULL, NULL, 1, '0000-00-00 00:00:00'),
(169, 'Report', 'Reports', 'Reports', 'REPORTS', 'Data Quality Reports', 'DataQuality', 'DataQuality', NULL, 162, 0, 169, NULL, NULL, 1, '0000-00-00 00:00:00'),
(170, 'Report', 'Reports', 'Reports', 'REPORTS', 'Indicator Reports', 'Indicator', 'Indicator', NULL, 162, 0, 170, NULL, NULL, 1, '0000-00-00 00:00:00'),
(171, 'Report', NULL, 'Report', 'REPORTS', 'Custom Reports', 'index', 'index|^reports', NULL, 162, 0, 171, NULL, NULL, 1, '0000-00-00 00:00:00'),
(172, 'Home', NULL, 'Home', NULL, 'My Details', 'details', 'details', NULL, -1, 0, 172, NULL, NULL, 1, '0000-00-00 00:00:00'),
(173, 'Home', NULL, 'Home', NULL, 'Change Password', 'password', 'password', NULL, 172, 0, 173, NULL, NULL, 1, '0000-00-00 00:00:00'),
(174, 'Home', NULL, 'Home', NULL, 'Contact', 'support', 'support', NULL, -1, 0, 174, NULL, NULL, 1, '0000-00-00 00:00:00'),
(175, 'Home', NULL, 'Home', NULL, 'System Information', 'systemInfo', 'systemInfo', NULL, 174, 0, 175, NULL, NULL, 1, '0000-00-00 00:00:00'),
(176, 'Home', NULL, 'Home', NULL, 'License', 'license', 'license', NULL, 174, 0, 176, NULL, NULL, 1, '0000-00-00 00:00:00'),
(177, 'Home', NULL, 'Home', NULL, 'Partners', 'partners', 'partners', NULL, 174, 0, 177, NULL, NULL, 1, '0000-00-00 00:00:00');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
