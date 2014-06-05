--
-- Table structure for table `config_items`
--

DROP TABLE IF EXISTS `config_items`;
CREATE TABLE IF NOT EXISTS `config_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `value` varchar(500) NOT NULL,
  `default_value` varchar(500) DEFAULT NULL,
  `editable` int(1) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `field_type` varchar(50) NOT NULL,
  `option_type` varchar(50) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=108 ;

--
-- Dumping data for table `config_items`
--

INSERT INTO `config_items` (`id`, `name`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'version', 'System', 'Version', 'Version 2.0', 'Version 1.0', 0, 1, '', '', 1, '2013-12-12 13:56:00', 1, '0000-00-00 00:00:00'),
(2, 'adaptation', 'System', 'Title', 'OpenEMIS: Open Education Management Information System', 'OpenEMIS: Open Education Management Information System', 1, 1, '', '', 108, '2014-04-02 16:48:23', 1, '0000-00-00 00:00:00'),
(3, 'footer', 'System', 'Footer', '&copy; year UNESCO', '&copy; 2013 UNESCO', 0, 1, '', '', 1, '2013-12-12 13:56:00', 1, '0000-00-00 00:00:00'),
(4, 'time_format', 'System', 'Time Format', 'H:i:s', 'H:i:s', 0, 1, '', '', 1, '2013-12-12 13:56:00', 1, '0000-00-00 00:00:00'),
(5, 'date_format', 'System', 'Date Format', 'F d, Y', 'Y-m-d', 1, 1, 'Dropdown', 'date_format', 108, '2014-04-02 16:48:23', 1, '0000-00-00 00:00:00'),
(6, 'dashboard_notice', 'System', 'Notice', 'Welcome to OpenEMIS.', 'Welcome to OpenEMIS.', 1, 1, '', '', 108, '2014-04-02 16:48:23', 1, '0000-00-00 00:00:00'),
(7, 'dashboard_img_width', 'System', 'Image Width', '700', '700', 0, 1, '', '', 1, '2013-12-12 13:56:00', 1, '0000-00-00 00:00:00'),
(8, 'dashboard_img_height', 'System', 'Image Height', '320', '320', 0, 1, '', '', 1, '2013-12-12 13:56:00', 1, '0000-00-00 00:00:00'),
(9, 'dashboard_img_default', 'System', 'Image Default Id', '1', '1', 0, 1, '', '', 1, '2013-12-12 13:56:00', 1, '0000-00-00 00:00:00'),
(10, 'dashboard_img_size_limit', 'System', 'File Size Limit', '2097152', '2097152', 0, 1, '', '', 1, '2013-12-12 13:56:00', 1, '0000-00-00 00:00:00'),
(11, 'support_phone', 'Help', 'Telephone', '+65 6659 6068', '+65 6659 6068', 0, 1, '', '', 1, '2013-12-12 13:56:00', 1, '0000-00-00 00:00:00'),
(12, 'support_email', 'Help', 'Email', 'support@openemis.org', 'support@openemis.org', 0, 1, '', '', 1, '2013-12-12 13:56:00', 1, '0000-00-00 00:00:00'),
(13, 'support_address', 'Help', 'Address', '18 Sin Ming Lane #06-38 Midview City Singapore 573960', '18 Sin Ming Lane #06-38 Midview City Singapore 573960', 0, 1, '', '', 1, '2013-12-12 13:56:00', 1, '0000-00-00 00:00:00'),
(14, 'currency', 'System', 'Currency', 'PM', '$', 1, 1, '', '', 108, '2014-04-02 16:48:23', 1, '2013-01-07 00:00:00'),
(15, 'lowest_year', 'System', 'Lowest Year', '1950', '1950', 0, 1, '', '', NULL, NULL, 1, '2013-01-07 00:00:00'),
(16, 'language', 'System', 'Language', 'eng', 'eng', 1, 1, 'Dropdown', 'language', 108, '2014-04-02 16:48:23', 1, '2013-01-07 00:00:00'),
(17, 'report_discrepancy_variationpercent', 'Data Discrepancy', 'Data Discrepancy', '12', '0', 1, 1, '', '', 1, '2014-04-24 13:32:48', 1, '0000-00-00 00:00:00'),
(18, 'report_outlier_max_age', 'Data Outliers', 'Data Outliers', '21', '0', 1, 1, '', '', 1, '2014-04-24 13:43:09', 1, '0000-00-00 00:00:00'),
(19, 'report_outlier_min_age', 'Data Outliers', 'Minimum Student Age', '1', '0', 1, 1, '', '', 108, '2014-04-02 16:48:23', 1, '0000-00-00 00:00:00'),
(20, 'report_outlier_max_student', 'Data Outliers', 'Maximum Student Number', '21', '0', 1, 1, '', '', 108, '2014-04-02 16:48:23', 1, '0000-00-00 00:00:00'),
(21, 'report_outlier_min_student', 'Data Outliers', 'Minimum Student Number', '23131230', '0', 1, 1, '', '', 108, '2014-04-02 16:48:24', 1, '0000-00-00 00:00:00'),
(22, 'yearbook_organization_name', 'Year Book Report', 'Organization Name', 'Ministry of Education', 'Ministry of Education', 1, 1, '', '', 108, '2014-04-02 16:48:24', 1, '0000-00-00 00:00:00'),
(23, 'yearbook_school_year', 'Year Book Report', 'School Year', '7', '1', 1, 1, 'Dropdown', 'database:SchoolYear', 108, '2014-04-02 16:48:24', 1, '0000-00-00 00:00:00'),
(24, 'yearbook_title', 'Year Book Report', 'Title', 'Education Yearbook - For PNG', 'Education Yearbook', 1, 1, '', '', 108, '2014-04-02 16:48:24', 1, '0000-00-00 00:00:00'),
(25, 'yearbook_publication_date', 'Year Book Report', 'Year Book Report', '25-04-2014', '2013-01-01', 1, 1, 'Datepicker', '', 1, '2014-04-24 14:32:45', 1, '0000-00-00 00:00:00'),
(26, 'yearbook_logo', 'Year Book Report', 'Yearbook Logo', '9', '9', 1, 1, 'File', '', 108, '2014-04-02 16:48:24', 1, '0000-00-00 00:00:00'),
(27, 'yearbook_orientation', 'Year Book Report', 'Page Orientation', 'L', 'L', 1, 1, 'Dropdown', 'yearbook_orientation', 108, '2014-04-02 16:48:24', 1, '0000-00-00 00:00:00'),
(28, 'student_prefix', 'Auto Generated OpenEMIS ID', 'Student Prefix', ',0', ',0', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(29, 'teacher_prefix', 'Auto Generated OpenEMIS ID', 'Teacher Prefix', ',1', ',0', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(30, 'staff_prefix', 'Auto Generated OpenEMIS ID', 'Staff Prefix', 'AGN-STAF,1', ',0', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(31, 'institution_code', 'Custom Validation', 'Institution Code', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(32, 'institution_telephone', 'Custom Validation', 'Institution Telephone', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(33, 'institution_fax', 'Custom Validation', 'Institution Fax', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(34, 'institution_postal_code', 'Custom Validation', 'Institution Postal Code', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(35, 'institution_site_code', 'Custom Validation', 'Institution Site Code', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(36, 'institution_site_telephone', 'Custom Validation', 'Institution Site Telephone', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(37, 'institution_site_fax', 'Custom Validation', 'Institution Site Fax', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(38, 'institution_site_postal_code', 'Custom Validation', 'Institution Site Postal Code', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(39, 'student_identification', 'Custom Validation', 'Student Identification', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(40, 'student_telephone', 'Custom Validation', 'Student Telephone', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(41, 'student_postal_code', 'Custom Validation', 'Student Postal Code', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(42, 'teacher_identification', 'Custom Validation', 'Teacher Identification', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(43, 'teacher_telephone', 'Custom Validation', 'Teacher Telephone', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:24', 0, '0000-00-00 00:00:00'),
(44, 'teacher_postal_code', 'Custom Validation', 'Teacher Postal Code', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:25', 0, '0000-00-00 00:00:00'),
(45, 'staff_identification', 'Custom Validation', 'Staff Identification', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:25', 0, '0000-00-00 00:00:00'),
(46, 'staff_telephone', 'Custom Validation', 'Staff Telephone', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:25', 0, '0000-00-00 00:00:00'),
(47, 'staff_postal_code', 'Custom Validation', 'Staff Postal Code', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:25', 0, '0000-00-00 00:00:00'),
(48, 'change_password', 'System', 'Change Administrator password', '1', '1', 0, 0, '', '', NULL, NULL, 0, '0000-00-00 00:00:00'),
(49, 'host', 'LDAP Configuration', 'LDAP Server', 'ldap.testathon.net', 'ldap.testathon.net', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '2013-08-20 14:46:02'),
(50, 'port', 'LDAP Configuration', 'Port', '389', '389', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '2013-08-20 14:46:02'),
(51, 'version', 'LDAP Configuration', 'Version', '3', '3', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '2013-08-20 14:46:02'),
(52, 'base_dn', 'LDAP Configuration', 'Base DN', 'OU=users,DC=testathon,DC=net', 'OU=users,DC=testathon,DC=net', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '2013-08-20 14:46:02'),
(53, 'test_connection', 'LDAP Configuration', 'Test Connection', '', '', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '2013-08-20 14:46:02'),
(54, 'authentication_type', 'Authentication', 'Type', 'Local', 'Local', 1, 1, 'Dropdown', 'authentication_type', 1, '2014-04-24 12:58:00', 1, '2013-08-20 14:46:02'),
(55, 'where_is_my_school_title', 'Where''s My School Config', 'Title', 'Where is Umairah''s School?', '', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '0000-00-00 00:00:00'),
(56, 'where_is_my_school_url', 'Where''s My School Config', 'Where is my School URL', 'http://tst.openemis.org/demo', 'http://www.openemis.org/demo', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '0000-00-00 00:00:00'),
(57, 'where_is_my_school_start_long', 'Where''s My School Config', 'Starting Longitude', '153.32227', '0', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '0000-00-00 00:00:00'),
(58, 'where_is_my_school_start_lat', 'Where''s My School Config', 'Starting Latitude', '-7.60266', '0', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '0000-00-00 00:00:00'),
(59, 'where_is_my_school_start_range', 'Where''s My School Config', 'Starting Range', '2613016', '2000000', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '0000-00-00 00:00:00'),
(60, 'language_menu', 'System', 'Show Language Option during Login', '1', '0', 1, 1, 'Dropdown', 'yes_no', 1, '2014-04-24 11:47:38', 1, '0000-00-00 00:00:00'),
(61, 'sms_provider_url', 'SMS', 'SMS Provider URL', 'http://www.smsdome.com/api/http/sendsms.aspx?login=KORD&password=KT131210&responseformat=JSON', 'http://www.smsdome.com/api/http/sendsms.aspx?login=KORD&password=KT131210&responseformat=JSON', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '2013-12-19 10:53:33'),
(62, 'sms_number', 'SMS', 'SMS Number', 'receivers', 'receivers', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '2013-12-19 10:53:33'),
(63, 'sms_content', 'SMS', 'SMS Content', 'content', 'content', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '2013-12-19 10:53:33'),
(64, 'sms_retry_times', 'SMS', 'SMS Retry Times', '10', '3', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '2013-12-19 10:53:33'),
(65, 'sms_retry_wait', 'SMS', 'SMS Retry Delay', '5', '5', 1, 1, '', '', 108, '2014-04-02 16:48:25', 1, '2013-12-19 10:53:33'),
(66, 'training_credit_hour', 'Training', 'Training', '2', '10', 1, 1, '', '', 1, '2014-04-24 13:48:41', 0, '2014-02-21 00:00:00'),
(67, 'country_id', 'Nationality', 'Default Country', '171', '1', 1, 1, 'Dropdown', 'database:Country', 108, '2014-04-02 16:48:25', 1, '0000-00-00 00:00:00'),
(69, 'student_contacts', 'Wizard - Add New Student', 'Contacts', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:25', 0, '0000-00-00 00:00:00'),
(70, 'student_identities', 'Wizard - Add New Student', 'Identities', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:25', 0, '0000-00-00 00:00:00'),
(71, 'student_nationalities', 'Wizard - Add New Student', 'Nationalities', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:25', 0, '0000-00-00 00:00:00'),
(72, 'student_languages', 'Wizard - Add New Student', 'Languages', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:25', 0, '0000-00-00 00:00:00'),
(73, 'student_bankAccounts', 'Wizard - Add New Student', 'Bank Accounts', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:25', 0, '0000-00-00 00:00:00'),
(74, 'student_comments', 'Wizard - Add New Student', 'Comments', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:25', 0, '0000-00-00 00:00:00'),
(75, 'student_specialNeed', 'Wizard - Add New Student', 'Special Needs', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:25', 0, '0000-00-00 00:00:00'),
(76, 'student_award', 'Wizard - Add New Student', 'Awards', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:25', 0, '0000-00-00 00:00:00'),
(77, 'student_attachments', 'Wizard - Add New Student', 'Attachments', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(80, 'teacher_contacts', 'Wizard - Add New Teacher', 'Contacts', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(81, 'teacher_identities', 'Wizard - Add New Teacher', 'Identities', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(82, 'teacher_nationalities', 'Wizard - Add New Teacher', 'Nationalities', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(83, 'teacher_languages', 'Wizard - Add New Teacher', 'Languages', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(84, 'teacher_bankAccounts', 'Wizard - Add New Teacher', 'Bank Accounts', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(85, 'teacher_comments', 'Wizard - Add New Teacher', 'Comments', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(86, 'teacher_specialNeed', 'Wizard - Add New Teacher', 'Special Needs', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(87, 'teacher_award', 'Wizard - Add New Teacher', 'Awards', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(88, 'teacher_membership', 'Wizard - Add New Teacher', 'Memberships', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(89, 'teacher_license', 'Wizard - Add New Teacher', 'Licenses', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(90, 'teacher_attachments', 'Wizard - Add New Teacher', 'Attachments', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(93, 'staff_contacts', 'Wizard - Add New Staff', 'Contacts', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(94, 'staff_identities', 'Wizard - Add New Staff', 'Identities', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(95, 'staff_nationalities', 'Wizard - Add New Staff', 'Nationalities', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(96, 'staff_languages', 'Wizard - Add New Staff', 'Languages', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(97, 'staff_bankAccounts', 'Wizard - Add New Staff', 'Bank Accounts', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(98, 'staff_comments', 'Wizard - Add New Staff', 'Comments', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(99, 'staff_specialNeed', 'Wizard - Add New Staff', 'Special Needs', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(100, 'staff_award', 'Wizard - Add New Staff', 'Awards', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(101, 'staff_membership', 'Wizard - Add New Staff', 'Memberships', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(102, 'staff_license', 'Wizard - Add New Staff', 'Licenses', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(103, 'staff_attachments', 'Wizard - Add New Staff', 'Attachments', '0', '0', 1, 1, 'Dropdown', 'wizard', 108, '2014-04-02 16:48:26', 0, '0000-00-00 00:00:00'),
(105, 'no_of_shifts', 'System', 'System', '3', '1', 1, 1, '', '', 1, '2014-04-24 13:46:52', 1, '0000-00-00 00:00:00'),
(106, 'admission_age_plus', 'Student Admission Age', 'Student Admission Age', '0', '0', 1, 1, '', '', 1, '2014-04-24 13:46:27', 1, '2014-03-21 00:00:00'),
(107, 'admission_age_minus', 'Student Admission Age', 'Admission Age Minus', '0', '0', 1, 1, '', '', 108, '2014-04-02 16:48:26', 1, '2014-03-21 00:00:00');


-- --------------------------------------------------------

--
-- Table structure for table `config_item_options`
--

DROP TABLE IF EXISTS `config_item_options`;
CREATE TABLE IF NOT EXISTS `config_item_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `option_type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `option` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(3) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=27 ;

--
-- Dumping data for table `config_item_options`
--

INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`) VALUES
(1, 'date_format', 'date(''Y-n-j'')', 'Y-m-d', 1, 1),
(2, 'date_format', 'date(''j-M-Y'')', 'd-M-Y', 2, 1),
(3, 'date_format', 'date(''j-n-Y'')', 'd-m-Y', 3, 1),
(4, 'date_format', 'date(''j/n/Y'')', 'd/m/Y', 4, 1),
(5, 'date_format', 'date(''n/d/Y'')', 'm/d/Y', 5, 1),
(6, 'date_format', 'date(''F j, Y'')', 'F d, Y', 6, 1),
(7, 'date_format', 'date(''jS F Y'')', 'dS F Y', 7, 1),
(10, 'authentication_type', 'Local', 'Local', 1, 1),
(11, 'authentication_type', 'LDAP', 'LDAP', 2, 1),
(12, 'language', 'العربية', 'ara', 1, 1),
(13, 'language', '中文', 'chi', 2, 1),
(14, 'language', 'English', 'eng', 3, 1),
(15, 'language', 'Français', 'fre', 4, 1),
(16, 'language', 'русский', 'ru', 5, 1),
(17, 'language', 'español', 'spa', 6, 1),
(18, 'yes_no', 'Yes', '1', 1, 1),
(19, 'yes_no', 'No', '0', 2, 1),
(20, 'wizard', 'Non-Mandatory', '0', 1, 1),
(21, 'wizard', 'Mandatory', '1', 2, 1),
(22, 'wizard', 'Excluded', '2', 3, 1),
(23, 'database:Country', 'Country.name', 'Country.id', 1, 1),
(24, 'database:SchoolYear', 'SchoolYear.name', 'SchoolYear.id', 1, 1),
(25, 'yearbook_orientation', 'Portrait', 'P', 1, 1),
(26, 'yearbook_orientation', 'Landscape', 'L', 2, 1);

