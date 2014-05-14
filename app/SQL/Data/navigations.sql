TRUNCATE TABLE `navigations`;
--
-- Dumping data for table `navigations`
--

INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'Institution', NULL, 'InstitutionSites', NULL, 'List of Institutions', 'index', 'index$|advanced', NULL, -1, 0, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(2, 'Institution', NULL, 'InstitutionSites', NULL, 'Add new Institution', 'add', 'add$', NULL, 1, 0, 2, NULL, NULL, 1, '0000-00-00 00:00:00'),
(3, 'Institution', NULL, 'InstitutionSites', 'GENERAL', 'Overview', 'view', 'view$|^edit$|^history', NULL, -1, 0, 3, NULL, NULL, 1, '0000-00-00 00:00:00'),
(4, 'Institution', NULL, 'InstitutionSites', 'GENERAL', 'Shifts', 'shifts', 'shifts', NULL, 3, 0, 4, NULL, NULL, 1, '0000-00-00 00:00:00'),
(5, 'Institution', NULL, 'InstitutionSites', 'GENERAL', 'Attachments', 'attachments', 'attachments', NULL, 3, 0, 5, NULL, NULL, 1, '0000-00-00 00:00:00'),
(6, 'Institution', NULL, 'InstitutionSites', 'GENERAL', 'Bank Accounts', 'bankAccounts', 'bankAccounts', NULL, 3, 0, 6, NULL, NULL, 1, '0000-00-00 00:00:00'),
(7, 'Institution', NULL, 'InstitutionSites', 'GENERAL', 'More', 'additional', 'additional', NULL, 3, 0, 7, NULL, NULL, 1, '0000-00-00 00:00:00'),
(8, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Programmes', 'programmes', 'programmes', NULL, 3, 0, 8, NULL, NULL, 1, '0000-00-00 00:00:00'),
(9, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Positions', 'positions', '^positions|^positionsHistory', NULL, 3, 0, 9, NULL, NULL, 2, '0000-00-00 00:00:01'),
(10, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Students', 'students', 'students', NULL, 3, 0, 10, NULL, NULL, 1, '0000-00-00 00:00:00'),
(11, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Staff', 'staff', 'staff', NULL, 3, 0, 11, NULL, NULL, 1, '0000-00-00 00:00:00'),
(12, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Classes', 'classes', 'classes', NULL, 3, 0, 12, NULL, NULL, 1, '0000-00-00 00:00:00'),
(13, 'Institution', NULL, 'Census', 'TOTALS', 'Verifications', 'verifications', 'verifications', NULL, 3, 0, 13, NULL, NULL, 1, '0000-00-00 00:00:00'),
(14, 'Institution', NULL, 'Census', 'TOTALS', 'Students', 'enrolment', 'enrolment', NULL, 3, 0, 14, NULL, NULL, 1, '0000-00-00 00:00:00'),
(15, 'Institution', NULL, 'Census', 'TOTALS', 'Teachers', 'teachers', 'teachers', NULL, 3, 0, 15, NULL, NULL, 1, '0000-00-00 00:00:00'),
(16, 'Institution', NULL, 'Census', 'TOTALS', 'Staff', 'staff', 'staff', NULL, 3, 0, 16, NULL, NULL, 1, '0000-00-00 00:00:00'),
(17, 'Institution', NULL, 'Census', 'TOTALS', 'Classes', 'classes', 'classes', NULL, 3, 0, 17, NULL, NULL, 1, '0000-00-00 00:00:00'),
(18, 'Institution', NULL, 'Census', 'TOTALS', 'Shifts', 'shifts', 'shifts', NULL, 3, 0, 18, NULL, NULL, 1, '0000-00-00 00:00:00'),
(19, 'Institution', NULL, 'Census', 'TOTALS', 'Graduates', 'graduates', 'graduates', NULL, 3, 0, 19, NULL, NULL, 1, '0000-00-00 00:00:00'),
(20, 'Institution', NULL, 'Census', 'TOTALS', 'Attendance', 'attendance', 'attendance', NULL, 3, 0, 20, NULL, NULL, 1, '0000-00-00 00:00:00'),
(21, 'Institution', NULL, 'Census', 'TOTALS', 'Results', 'assessments', 'assessments', NULL, 3, 0, 21, NULL, NULL, 1, '0000-00-00 00:00:00'),
(22, 'Institution', NULL, 'Census', 'TOTALS', 'Behaviour', 'behaviour', 'behaviour', NULL, 3, 0, 22, NULL, NULL, 1, '0000-00-00 00:00:00'),
(23, 'Institution', NULL, 'Census', 'TOTALS', 'Textbooks', 'textbooks', 'textbooks', NULL, 3, 0, 23, NULL, NULL, 1, '0000-00-00 00:00:00'),
(24, 'Institution', NULL, 'Census', 'TOTALS', 'Infrastructure', 'infrastructure', 'infrastructure', NULL, 3, 0, 24, NULL, NULL, 1, '0000-00-00 00:00:00'),
(25, 'Institution', NULL, 'Census', 'TOTALS', 'Finances', 'finances', 'finances', NULL, 3, 0, 25, NULL, NULL, 1, '0000-00-00 00:00:00'),
(26, 'Institution', NULL, 'Census', 'TOTALS', 'More', 'otherforms', 'otherforms', NULL, 3, 0, 26, NULL, NULL, 1, '0000-00-00 00:00:00'),
(27, 'Institution', NULL, 'Quality', 'QUALITY', 'Rubrics', 'qualityRubric', 'qualityRubric', NULL, 3, 0, 27, NULL, NULL, 1, '0000-00-00 00:00:00'),
(28, 'Institution', NULL, 'Quality', 'QUALITY', 'Visits', 'qualityVisit', 'qualityVisit', NULL, 3, 0, 28, NULL, NULL, 1, '0000-00-00 00:00:00'),
(29, 'Institution', NULL, 'InstitutionSites', 'REPORTS', 'General', 'reportsGeneral', 'reportsGeneral', NULL, 3, 0, 29, NULL, NULL, 1, '0000-00-00 00:00:00'),
(30, 'Institution', NULL, 'InstitutionSites', 'REPORTS', 'Details', 'reportsDetails', 'reportsDetails', NULL, 3, 0, 30, NULL, NULL, 1, '0000-00-00 00:00:00'),
(31, 'Institution', NULL, 'InstitutionSites', 'REPORTS', 'Totals', 'reportsTotals', 'reportsTotals', NULL, 3, 0, 31, NULL, NULL, 1, '0000-00-00 00:00:00'),
(32, 'Institution', NULL, 'InstitutionSites', 'REPORTS', 'Quality', 'reportsQuality', 'reportsQuality', NULL, 3, 0, 32, NULL, NULL, 1, '0000-00-00 00:00:00'),
(33, 'Administration', NULL, 'Areas', 'SYSTEM SETUP', 'Administrative Boundaries', 'index', 'index$|levels|edit|EducationArea|$', NULL, -1, 0, 33, NULL, NULL, 1, '0000-00-00 00:00:00'),
(34, 'Administration', NULL, 'Education', 'SYSTEM SETUP', 'Education Structure', 'index', 'index$|setup', NULL, 33, 0, 34, NULL, NULL, 1, '0000-00-00 00:00:00'),
(35, 'Administration', NULL, 'Assessment', 'SYSTEM SETUP', 'National Assessments', 'index', '^index|assessment', NULL, 33, 0, 35, NULL, NULL, 1, '0000-00-00 00:00:00'),
(36, 'Administration', NULL, 'FieldOption', 'SYSTEM SETUP', 'Field Options', 'index', 'index|view|edit|add', NULL, 33, 0, 36, NULL, NULL, 1, '0000-00-00 00:00:00'),
(37, 'Administration', NULL, 'Config', 'SYSTEM SETUP', 'System Configurations', 'index', 'index$|edit$|^dashboard|view$', NULL, 33, 0, 37, NULL, NULL, 1, '0000-00-00 00:00:00'),
(38, 'Administration', NULL, 'Security', 'ACCOUNTS &amp; SECURITY', 'Users', 'users', 'users', NULL, 33, 0, 38, NULL, NULL, 1, '0000-00-00 00:00:00'),
(39, 'Administration', NULL, 'Security', 'ACCOUNTS &amp; SECURITY', 'Groups', 'groups', '^group', NULL, 33, 0, 39, NULL, NULL, 1, '0000-00-00 00:00:00'),
(40, 'Administration', NULL, 'Security', 'ACCOUNTS &amp; SECURITY', 'Roles', 'roles', '^role|^permissions', NULL, 33, 0, 40, NULL, NULL, 1, '0000-00-00 00:00:00'),
(41, 'Administration', NULL, 'Population', 'NATIONAL DENOMINATORS', 'Population', 'index', 'index$|edit$', NULL, 33, 0, 41, NULL, NULL, 1, '0000-00-00 00:00:00'),
(42, 'Administration', NULL, 'Finance', 'NATIONAL DENOMINATORS', 'Finance', 'index', 'index$|edit$|financePerEducationLevel$', NULL, 33, 0, 42, NULL, NULL, 1, '0000-00-00 00:00:00'),
(43, 'Administration', 'DataProcessing', 'DataProcessing', 'DATA PROCESSING', 'Build', 'build', 'build', NULL, 33, 0, 43, NULL, NULL, 1, '0000-00-00 00:00:00'),
(44, 'Administration', 'DataProcessing', 'DataProcessing', 'DATA PROCESSING', 'Generate', 'genReports', '^gen', NULL, 33, 0, 44, NULL, NULL, 1, '0000-00-00 00:00:00'),
(45, 'Administration', 'DataProcessing', 'DataProcessing', 'DATA PROCESSING', 'Export', 'export', 'export', NULL, 33, 0, 45, NULL, NULL, 1, '0000-00-00 00:00:00'),
(46, 'Administration', 'DataProcessing', 'DataProcessing', 'DATA PROCESSING', 'Processes', 'processes', 'processes', NULL, 33, 0, 46, NULL, NULL, 1, '0000-00-00 00:00:00'),
(47, 'Administration', 'Database', 'Database', 'DATABASE', 'Backup', 'backup', 'backup', NULL, 33, 0, 47, NULL, NULL, 1, '0000-00-00 00:00:00'),
(48, 'Administration', 'Database', 'Database', 'DATABASE', 'Restore', 'restore', 'restore', NULL, 33, 0, 48, NULL, NULL, 1, '0000-00-00 00:00:00'),
(49, 'Administration', 'Survey', 'Survey', 'SURVEY', 'New', 'index', 'index$|^add$|^edit$', NULL, 33, 0, 49, NULL, NULL, 1, '0000-00-00 00:00:00'),
(50, 'Administration', 'Survey', 'Survey', 'SURVEY', 'Completed', 'import', 'import$|^synced$', NULL, 33, 0, 50, NULL, NULL, 1, '0000-00-00 00:00:00'),
(51, 'Administration', 'Sms', 'Sms', 'SMS', 'Messages', 'messages', 'messages', NULL, 33, 0, 51, NULL, NULL, 1, '0000-00-00 00:00:00'),
(52, 'Administration', 'Sms', 'Sms', 'SMS', 'Responses', 'responses', 'responses', NULL, 33, 0, 52, NULL, NULL, 1, '0000-00-00 00:00:00'),
(53, 'Administration', 'Sms', 'Sms', 'SMS', 'Logs', 'logs', 'logs', NULL, 33, 0, 53, NULL, NULL, 1, '0000-00-00 00:00:00'),
(54, 'Administration', 'Sms', 'Sms', 'SMS', 'Reports', 'reports', 'reports', NULL, 33, 0, 54, NULL, NULL, 1, '0000-00-00 00:00:00'),
(55, 'Administration', 'Training', 'Training', 'TRAINING', 'Courses', 'course', 'course', NULL, 33, 0, 55, NULL, NULL, 1, '0000-00-00 00:00:00'),
(56, 'Administration', 'Training', 'Training', 'TRAINING', 'Sessions', 'session', 'session', NULL, 33, 0, 56, NULL, NULL, 1, '0000-00-00 00:00:00'),
(57, 'Administration', 'Training', 'Training', 'TRAINING', 'Results', 'result', 'result', NULL, 33, 0, 57, NULL, NULL, 1, '0000-00-00 00:00:00'),
(58, 'Administration', NULL, 'Quality', 'QUALITY', 'Rubrics', 'rubricsTemplates', 'rubricsTemplates', NULL, 33, 0, 58, NULL, NULL, 1, '0000-00-00 00:00:00'),
(59, 'Administration', NULL, 'Quality', 'QUALITY', 'Status', 'status', 'status', NULL, 33, 0, 59, NULL, NULL, 1, '0000-00-00 00:00:00'),
(60, 'Student', 'Students', 'Students', NULL, 'List of Students', 'index', 'index$|advanced', NULL, -1, 0, 60, NULL, NULL, 1, '0000-00-00 00:00:00'),
(61, 'Student', 'Students', 'Students', NULL, 'Add new Student', 'add', 'add$', NULL, 60, 0, 61, NULL, NULL, 1, '0000-00-00 00:00:00'),
(62, 'Student', 'Students', 'Students', 'GENERAL', 'Overview', 'view', 'view$|^edit$|\\bhistory\\b', NULL, -1, 1, 62, NULL, NULL, 1, '0000-00-00 00:00:00'),
(63, 'Student', 'Students', 'Students', 'GENERAL', 'Contacts', 'contacts', 'contacts', NULL, 62, 1, 63, NULL, NULL, 1, '0000-00-00 00:00:00'),
(64, 'Student', 'Students', 'Students', 'GENERAL', 'Identities', 'identities', 'identities', NULL, 62, 1, 64, NULL, NULL, 1, '0000-00-00 00:00:00'),
(65, 'Student', 'Students', 'Students', 'GENERAL', 'Nationalities', 'nationalities', 'nationalities', NULL, 62, 1, 65, NULL, NULL, 1, '0000-00-00 00:00:00'),
(66, 'Student', 'Students', 'Students', 'GENERAL', 'Languages', 'languages', 'languages', NULL, 62, 1, 66, NULL, NULL, 1, '0000-00-00 00:00:00'),
(67, 'Student', 'Students', 'Students', 'GENERAL', 'Bank Accounts', 'bankAccounts', 'bankAccounts', NULL, 62, 1, 67, NULL, NULL, 1, '0000-00-00 00:00:00'),
(68, 'Student', 'Students', 'Students', 'GENERAL', 'Comments', 'comments', 'comments', NULL, 62, 1, 68, NULL, NULL, 1, '0000-00-00 00:00:00'),
(69, 'Student', 'Students', 'Students', 'GENERAL', 'Special Needs', 'specialNeed', '^specialNeed', NULL, 62, 1, 69, NULL, NULL, 1, '0000-00-00 00:00:00'),
(70, 'Student', 'Students', 'Students', 'GENERAL', 'Awards', 'award', '^award', NULL, 62, 1, 70, NULL, NULL, 1, '0000-00-00 00:00:00'),
(71, 'Student', 'Students', 'Students', 'GENERAL', 'Attachments', 'attachments', 'attachments', NULL, 62, 1, 71, NULL, NULL, 1, '0000-00-00 00:00:00'),
(72, 'Student', 'Students', 'Students', 'GENERAL', 'More', 'additional', 'additional|^custFieldYrView$', NULL, 62, 1, 72, NULL, NULL, 1, '0000-00-00 00:00:00'),
(73, 'Student', 'Students', 'Students', 'DETAILS', 'Guardians', 'guardians', 'guardians', NULL, 62, 0, 73, NULL, NULL, 1, '0000-00-00 00:00:00'),
(74, 'Student', 'Students', 'Students', 'DETAILS', 'Classes', 'classes', 'classes', NULL, 62, 0, 74, NULL, NULL, 1, '0000-00-00 00:00:00'),
(75, 'Student', 'Students', 'Students', 'DETAILS', 'Attendance', 'attendance', 'attendance', NULL, 62, 0, 75, NULL, NULL, 1, '0000-00-00 00:00:00'),
(76, 'Student', 'Students', 'Students', 'DETAILS', 'Behaviour', 'behaviour', 'behaviour|^behaviourView$', NULL, 62, 0, 76, NULL, NULL, 1, '0000-00-00 00:00:00'),
(77, 'Student', 'Students', 'Students', 'DETAILS', 'Results', 'assessments', 'assessments', NULL, 62, 0, 77, NULL, NULL, 1, '0000-00-00 00:00:00'),
(78, 'Student', 'Students', 'Students', 'DETAILS', 'Extracurricular', 'extracurricular', 'extracurricular', NULL, 62, 0, 78, NULL, NULL, 1, '0000-00-00 00:00:00'),
(79, 'Student', 'Students', 'Students', 'HEALTH', 'Overview', 'healthView', 'healthView|healthEdit', NULL, 62, 0, 79, NULL, NULL, 1, '0000-00-00 00:00:00'),
(80, 'Student', 'Students', 'Students', 'HEALTH', 'History', 'healthHistory', '^healthHistory', NULL, 62, 0, 80, NULL, NULL, 1, '0000-00-00 00:00:00'),
(81, 'Student', 'Students', 'Students', 'HEALTH', 'Family', 'healthFamily', '^healthFamily', NULL, 62, 0, 81, NULL, NULL, 1, '0000-00-00 00:00:00'),
(82, 'Student', 'Students', 'Students', 'HEALTH', 'Immunizations', 'healthImmunization', '^healthImmunization', NULL, 62, 0, 82, NULL, NULL, 1, '0000-00-00 00:00:00'),
(83, 'Student', 'Students', 'Students', 'HEALTH', 'Medications', 'healthMedication', '^healthMedication', NULL, 62, 0, 83, NULL, NULL, 1, '0000-00-00 00:00:00'),
(84, 'Student', 'Students', 'Students', 'HEALTH', 'Allergies', 'healthAllergy', '^healthAllergy', NULL, 62, 0, 84, NULL, NULL, 1, '0000-00-00 00:00:00'),
(85, 'Student', 'Students', 'Students', 'HEALTH', 'Tests', 'healthTest', '^healthTest', NULL, 62, 0, 85, NULL, NULL, 1, '0000-00-00 00:00:00'),
(86, 'Student', 'Students', 'Students', 'HEALTH', 'Consultations', 'healthConsultation', '^healthConsultation', NULL, 62, 0, 86, NULL, NULL, 1, '0000-00-00 00:00:00'),
(87, 'Staff', 'Staff', 'Staff', NULL, 'List of Staff', 'index', 'index$|advanced', NULL, -1, 0, 87, NULL, NULL, 1, '0000-00-00 00:00:00'),
(88, 'Staff', 'Staff', 'Staff', NULL, 'Add new Staff', 'add', 'add$', NULL, 87, 0, 88, NULL, NULL, 1, '0000-00-00 00:00:00'),
(89, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Overview', 'view', 'view$|^edit$|\\bhistory\\b', NULL, -1, 1, 89, NULL, NULL, 1, '0000-00-00 00:00:00'),
(90, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Contacts', 'contacts', 'contacts', NULL, 89, 1, 90, NULL, NULL, 1, '0000-00-00 00:00:00'),
(91, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Identities', 'identities', 'identities', NULL, 89, 1, 91, NULL, NULL, 1, '0000-00-00 00:00:00'),
(92, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Nationalities', 'nationalities', 'nationalities', NULL, 89, 1, 92, NULL, NULL, 1, '0000-00-00 00:00:00'),
(93, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Languages', 'languages', 'languages', NULL, 89, 1, 93, NULL, NULL, 1, '0000-00-00 00:00:00'),
(94, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Bank Accounts', 'bankAccounts', 'bankAccounts', NULL, 89, 1, 94, NULL, NULL, 1, '0000-00-00 00:00:00'),
(95, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Comments', 'comments', 'comments', NULL, 89, 1, 95, NULL, NULL, 1, '0000-00-00 00:00:00'),
(96, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Special Needs', 'specialNeed', '^specialNeed', NULL, 89, 1, 96, NULL, NULL, 1, '0000-00-00 00:00:00'),
(97, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Awards', 'award', '^award', NULL, 89, 1, 97, NULL, NULL, 1, '0000-00-00 00:00:00'),
(98, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Memberships', 'membership', '^membership', NULL, 89, 1, 98, NULL, NULL, 1, '0000-00-00 00:00:00'),
(99, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Licenses', 'license', '^license', NULL, 89, 1, 99, NULL, NULL, 1, '0000-00-00 00:00:00'),
(100, 'Staff', 'Staff', 'Staff', 'GENERAL', 'Attachments', 'attachments', 'attachments', NULL, 89, 1, 100, NULL, NULL, 1, '0000-00-00 00:00:00'),
(101, 'Staff', 'Staff', 'Staff', 'GENERAL', 'More', 'additional', 'additional|^custFieldYrView$', NULL, 89, 1, 101, NULL, NULL, 1, '0000-00-00 00:00:00'),
(102, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Qualifications', 'qualifications', 'qualifications', NULL, 89, 0, 102, NULL, NULL, 1, '0000-00-00 00:00:00'),
(103, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Training', 'training', 'training$|trainingAdd$|trainingEdit$|trainingView$', NULL, 89, 0, 103, NULL, NULL, 1, '0000-00-00 00:00:00'),
(104, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Positions', 'positions', 'positions', NULL, 89, 0, 104, NULL, NULL, 1, '0000-00-00 00:00:00'),
(105, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Attendance', 'attendance', 'attendance', NULL, 89, 0, 105, NULL, NULL, 1, '0000-00-00 00:00:00'),
(106, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Leave', 'leaves', 'leaves', NULL, 89, 0, 106, NULL, NULL, 1, '0000-00-00 00:00:00'),
(107, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Behaviour', 'behaviour', 'behaviour|^behaviourView$', NULL, 89, 0, 107, NULL, NULL, 1, '0000-00-00 00:00:00'),
(108, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Extracurricular', 'extracurricular', 'extracurricular', NULL, 89, 0, 108, NULL, NULL, 1, '0000-00-00 00:00:00'),
(109, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Employment', 'employments', 'employments', NULL, 89, 0, 109, NULL, NULL, 1, '0000-00-00 00:00:00'),
(110, 'Staff', 'Staff', 'Staff', 'DETAILS', 'Salary', 'salaries', 'salaries', NULL, 89, 0, 110, NULL, NULL, 1, '0000-00-00 00:00:00'),
(111, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Overview', 'healthView', 'healthView|healthEdit', NULL, 89, 0, 111, NULL, NULL, 1, '0000-00-00 00:00:00'),
(112, 'Staff', 'Staff', 'Staff', 'HEALTH', 'History', 'healthHistory', '^healthHistory', NULL, 89, 0, 112, NULL, NULL, 1, '0000-00-00 00:00:00'),
(113, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Family', 'healthFamily', '^healthFamily', NULL, 89, 0, 113, NULL, NULL, 1, '0000-00-00 00:00:00'),
(114, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Immunizations', 'healthImmunization', '^healthImmunization', NULL, 89, 0, 114, NULL, NULL, 1, '0000-00-00 00:00:00'),
(115, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Medications', 'healthMedication', '^healthMedication', NULL, 89, 0, 115, NULL, NULL, 1, '0000-00-00 00:00:00'),
(116, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Allergies', 'healthAllergy', '^healthAllergy', NULL, 89, 0, 116, NULL, NULL, 1, '0000-00-00 00:00:00'),
(117, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Tests', 'healthTest', '^healthTest', NULL, 89, 0, 117, NULL, NULL, 1, '0000-00-00 00:00:00'),
(118, 'Staff', 'Staff', 'Staff', 'HEALTH', 'Consultations', 'healthConsultation', '^healthConsultation', NULL, 89, 0, 118, NULL, NULL, 1, '0000-00-00 00:00:00'),
(119, 'Staff', 'Staff', 'Staff', 'TRAINING', 'Needs', 'trainingNeed', '^trainingNeed', NULL, 89, 0, 119, NULL, NULL, 1, '0000-00-00 00:00:00'),
(120, 'Staff', 'Staff', 'Staff', 'TRAINING', 'Results', 'trainingResult', '^trainingResult', NULL, 89, 0, 120, NULL, NULL, 1, '0000-00-00 00:00:00'),
(121, 'Staff', 'Staff', 'Staff', 'TRAINING', 'Achievements', 'trainingSelfStudy', '^trainingSelfStudy', NULL, 89, 0, 121, NULL, NULL, 1, '0000-00-00 00:00:00'),
(122, 'Staff', 'Staff', 'Staff', 'REPORT', 'Quality', 'report', 'report|reportGen', NULL, 89, 0, 122, NULL, NULL, 1, '0000-00-00 00:00:00'),
(123, 'Report', 'Reports', 'Reports', 'REPORTS', 'Institution Reports', 'Institution', 'Institution', NULL, -1, 0, 123, NULL, NULL, 1, '0000-00-00 00:00:00'),
(124, 'Report', 'Reports', 'Reports', 'REPORTS', 'Student Reports', 'Student', 'Student', NULL, 123, 0, 124, NULL, NULL, 1, '0000-00-00 00:00:00'),
(125, 'Report', 'Reports', 'Reports', 'REPORTS', 'Teacher Reports', 'Teacher', 'Teacher', NULL, 123, 0, 125, NULL, NULL, 1, '0000-00-00 00:00:00'),
(126, 'Report', 'Reports', 'Reports', 'REPORTS', 'Staff Reports', 'Staff', 'Staff', NULL, 123, 0, 126, NULL, NULL, 1, '0000-00-00 00:00:00'),
(127, 'Report', 'Reports', 'Reports', 'REPORTS', 'Training Reports', 'Training', 'Training', NULL, 123, 0, 127, NULL, NULL, 1, '0000-00-00 00:00:00'),
(128, 'Report', 'Reports', 'Reports', 'REPORTS', 'Quality Assurance Reports', 'QualityAssurance', 'QualityAssurance', NULL, 123, 0, 128, NULL, NULL, 1, '0000-00-00 00:00:00'),
(129, 'Report', 'Reports', 'Reports', 'REPORTS', 'Consolidated Reports', 'Consolidated', 'Consolidated', NULL, 123, 0, 129, NULL, NULL, 1, '0000-00-00 00:00:00'),
(130, 'Report', 'Reports', 'Reports', 'REPORTS', 'Data Quality Reports', 'DataQuality', 'DataQuality', NULL, 123, 0, 130, NULL, NULL, 1, '0000-00-00 00:00:00'),
(131, 'Report', 'Reports', 'Reports', 'REPORTS', 'Indicator Reports', 'Indicator', 'Indicator', NULL, 123, 0, 131, NULL, NULL, 1, '0000-00-00 00:00:00'),
(132, 'Report', NULL, 'Report', 'REPORTS', 'Custom Reports', 'index', 'index|^reports', NULL, 123, 0, 132, NULL, NULL, 1, '0000-00-00 00:00:00'),
(133, 'Home', NULL, 'Home', NULL, 'My Details', 'details', 'details', NULL, -1, 0, 133, NULL, NULL, 1, '0000-00-00 00:00:00'),
(134, 'Home', NULL, 'Home', NULL, 'Change Password', 'password', 'password', NULL, 133, 0, 134, NULL, NULL, 1, '0000-00-00 00:00:00'),
(135, 'Home', NULL, 'Home', NULL, 'Contact', 'support', 'support', NULL, -1, 0, 135, NULL, NULL, 1, '0000-00-00 00:00:00'),
(136, 'Home', NULL, 'Home', NULL, 'System Information', 'systemInfo', 'systemInfo', NULL, 135, 0, 136, NULL, NULL, 1, '0000-00-00 00:00:00'),
(137, 'Home', NULL, 'Home', NULL, 'License', 'license', 'license', NULL, 135, 0, 137, NULL, NULL, 1, '0000-00-00 00:00:00'),
(138, 'Home', NULL, 'Home', NULL, 'Partners', 'partners', 'partners', NULL, 135, 0, 138, NULL, NULL, 1, '0000-00-00 00:00:00');
