--
-- 1. restore records for old reports
--

INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(123, 'Report', 'Reports', 'Reports', 'REPORTS', 'Institution Reports', 'Institution', 'Institution', NULL, -1, 0, 133, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(124, 'Report', 'Reports', 'Reports', 'REPORTS', 'Student Reports', 'Student', 'Student', NULL, 123, 0, 134, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(125, 'Report', 'Reports', 'Reports', 'REPORTS', 'Teacher Reports', 'Teacher', 'Teacher', NULL, 123, 0, 135, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(126, 'Report', 'Reports', 'Reports', 'REPORTS', 'Staff Reports', 'Staff', 'Staff', NULL, 123, 0, 136, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(127, 'Report', 'Reports', 'Reports', 'REPORTS', 'Training Reports', 'Training', 'Training', NULL, 123, 0, 137, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(128, 'Report', 'Reports', 'Reports', 'REPORTS', 'Quality Assurance Reports', 'QualityAssurance', 'QualityAssurance', NULL, 123, 0, 138, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(129, 'Report', 'Reports', 'Reports', 'REPORTS', 'Consolidated Reports', 'Consolidated', 'Consolidated', NULL, 123, 0, 139, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(130, 'Report', 'Reports', 'Reports', 'REPORTS', 'Data Quality Reports', 'DataQuality', 'DataQuality', NULL, 123, 0, 140, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(131, 'Report', 'Reports', 'Reports', 'REPORTS', 'Indicator Reports', 'Indicator', 'Indicator', NULL, 123, 0, 141, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(132, 'Report', NULL, 'Report', 'REPORTS', 'Custom Reports', 'index', 'index|^reports', NULL, 123, 0, 142, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(141, 'Report', 'Dashboards', 'Dashboards', 'REPORTS', 'Dashboards', 'dashboardReport', 'dashboardReport', NULL, 123, 0, 144, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(147, 'Report', 'OlapCube', 'OlapCube', 'REPORTS', 'Olap Reports', 'olapReport', '^olapReport', NULL, 123, 0, 143, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

--
-- 2. remove records for new report structrue
--

DELETE FROM `navigations` 
WHERE `module` LIKE 'Report' 
AND `id` > 147;

--
-- 3. remove category updates to table reports
--

UPDATE `reports` SET 
`category` = 'Institution Reports', 
`name` = 'Institution Custom Field Report' 
WHERE `reports`.`id` =13;

UPDATE `reports` SET 
`name` = 'Institution Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =11;

PDATE `reports` SET 
`name` = 'Institution Programme Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =15;

UPDATE `reports` SET 
`name` = 'Bank Accounts',
`category` = 'Institution Finance Reports' 
WHERE `reports`.`id` =14;

UPDATE `reports` SET 
`name` = 'Verification Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =21;

UPDATE `reports` SET 
`name` = 'Student Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =22;

UPDATE `reports` SET 
`name` = 'Training Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =24;

UPDATE `reports` SET 
`name` = 'Staff Report',
`category` = 'Institution Reports'
 WHERE `reports`.`id` =25;

UPDATE `reports` SET 
`name` = 'Class Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =26;

UPDATE `reports` SET 
`name` = 'Graduate Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =27;

UPDATE `reports` SET 
`name` = 'Attendance Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =28;

UPDATE `reports` SET 
`name` = 'Assessment Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =29;

UPDATE `reports` SET 
`name` = 'Behaviour Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =30;

UPDATE `reports` SET 
`name` = 'Textbook Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =31;

UPDATE `reports` SET 
`name` = 'Custom Field Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =32;

UPDATE `reports` SET 
`name` = 'Custom Table Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =33;

UPDATE `reports` SET 
`name` = 'Building Report',
`category` = 'Institution Reports', 
`module` = 'Infrastructure' 
WHERE `reports`.`id` =51;

UPDATE `reports` SET 
`name` = 'Room Report',
`category` = 'Institution Reports', 
`module` = 'Infrastructure' 
WHERE `reports`.`id` =52;

UPDATE `reports` SET 
`name` = 'Sanitation Report',
`category` = 'Institution Reports', 
`module` = 'Infrastructure' 
WHERE `reports`.`id` =53;

UPDATE `reports` SET 
`name` = 'Furniture Report',
`category` = 'Institution Reports', 
`module` = 'Infrastructure' 
WHERE `reports`.`id` =54;

UPDATE `reports` SET 
`name` = 'Resource Report',
`category` = 'Institution Reports', 
`module` = 'Infrastructure' 
WHERE `reports`.`id` =55;

UPDATE `reports` SET 
`name` = 'Energy Report',
`category` = 'Institution Reports', 
`module` = 'Infrastructure' 
WHERE `reports`.`id` =56;

UPDATE `reports` SET 
`name` = 'Water Report',
`category` = 'Institution Reports', 
`module` = 'Infrastructure' 
WHERE `reports`.`id` =57;

UPDATE `reports` SET 
`name` = 'Income Report',
`category` = 'Institution Reports', 
`module` = 'Finance' 
WHERE `reports`.`id` =71;

UPDATE `reports` SET 
`name` = 'Expenditure Report',
`category` = 'Institution Reports', 
`module` = 'Finance' 
WHERE `reports`.`id` =72;

UPDATE `reports` SET 
`name` = 'Student Report',
`category` = 'Student Reports' 
WHERE `reports`.`id` =81;

UPDATE `reports` SET 
`name` = 'Student Custom Field Report',
`category` = 'Student Reports' 
WHERE `reports`.`id` =82;

UPDATE `reports` SET 
`name` = 'Student Out of School Report',
`category` = 'Student Reports' 
WHERE `reports`.`id` =1028;

UPDATE `reports` SET 
`name` = 'Staff Report',
`category` = 'Staff Reports' 
WHERE `reports`.`id` =101;

UPDATE `reports` SET 
`name` = 'Staff Custom Field Report',
`category` = 'Staff Reports' 
WHERE `reports`.`id` =102;

UPDATE `reports` SET 
`name` = 'Training Course Report',
`category` = 'Training Reports' 
WHERE `reports`.`id` =1029;

UPDATE `reports` SET 
`name` = 'Training Course Completed Report',
`category` = 'Training Reports' 
WHERE `reports`.`id` =1030;

UPDATE `reports` SET 
`name` = 'Staff Training Need Report',
`category` = 'Training Reports' 
WHERE `reports`.`id` =1031;

UPDATE `reports` SET 
`name` = 'Training Course Uncompleted Report',
`category` = 'Training Reports' 
WHERE `reports`.`id` =1032;

UPDATE `reports` SET 
`name` = 'Training Course Uncompleted Report',
`category` = 'Training Reports' 
WHERE `reports`.`id` =1033;

UPDATE `reports` SET 
`name` = 'Training Exception Report',
`category` = 'Training Reports' 
WHERE `reports`.`id` =1034;

UPDATE `reports` SET 
`name` = 'Training Staff Statistic Report',
`category` = 'Training Reports' 
WHERE `reports`.`id` =1035;

UPDATE `reports` SET 
`name` = 'Year Book Report',
`category` = 'Consolidated Reports' 
WHERE `reports`.`id` =112;

UPDATE `reports` SET 
`name` = 'ECE QA Dashboard',
`category` = 'Dashboard Reports' 
WHERE `reports`.`id` =4001;

UPDATE `reports` SET 
`name` = 'Non-Responsive Schools Report',
`category` = 'Data Quality Reports' 
WHERE `reports`.`id` =151;

UPDATE `reports` SET 
`name` = 'Data Discrepancy Report',
`category` = 'Data Quality Reports' 
WHERE `reports`.`id` =152;

UPDATE `reports` SET 
`category` = 'Data Quality Reports' 
WHERE `reports`.`id` =153;

UPDATE `reports` SET 
`name` = 'Missing Coordinates Report',
`category` = 'Data Quality Reports' 
WHERE `reports`.`id` =154;

UPDATE `reports` SET 
`name` = 'Institution with No Area Report',
`category` = 'Data Quality Reports' 
WHERE `reports`.`id` =1038;

UPDATE `reports` SET 
`name` = 'Wheres My School Report',
`category` = 'Consolidated Reports' 
WHERE `reports`.`id` =111;

--
-- 4. restore existing security_functions records
--

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(91, 'Reports Index', 'Reports', 'Reports', 'Report', -1, 'index', NULL, NULL, NULL, NULL, 107, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(92, 'Institution Reports', 'Reports', 'Reports', 'Report', 91, 'Institution', NULL, NULL, NULL, '_view:InstitutionDownload', 108, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(93, 'Student Reports', 'Reports', 'Reports', 'Report', 91, 'Student', NULL, NULL, NULL, '_view:StudentDownload', 109, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(94, 'Teacher Reports', 'Reports', 'Reports', 'Report', 91, 'Teacher', NULL, NULL, NULL, '_view:TeacherDownload', 110, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(95, 'Staff Reports', 'Reports', 'Reports', 'Report', 91, 'Staff', NULL, NULL, NULL, '_view:StaffDownload', 111, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(96, 'Consolidated Reports', 'Reports', 'Reports', 'Report', 91, 'Consolidated', NULL, NULL, NULL, '_view:ConsolidatedDownload', 115, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(97, 'Indicator Reports', 'Reports', 'Reports', 'Report', 91, 'Indicator', NULL, NULL, NULL, '_view:downloadIndicator', 116, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(98, 'Data Quality Reports', 'Reports', 'Reports', 'Report', 91, 'DataQuality', NULL, NULL, NULL, '_view:DataQualityDownload', 117, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(178, 'Quality Assurance Reports', 'Reports', 'Reports', 'Report', 91, 'QualityAssurance', NULL, NULL, NULL, '_view:QualityAssuranceDownload|QualityAssuranceViewHtml', 113, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(182, 'Training Reports', 'Reports', 'Reports', 'Report', 91, 'Training', NULL, NULL, NULL, '_view:TrainingDownload|TrainingViewHtml', 112, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(185, 'Custom - Shared Reports', 'Report', 'Reports', 'Report', 91, 'index|reportsView', NULL, '_view:sharedReportAdd', '_view:sharedReportDelete', '_view:sharedReportRun', 118, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(186, 'Custom - My Reports', 'Report', 'Reports', 'Report', 91, 'index|reportsView', NULL, '_view:reportsNew|reportsWizard', '_view:reportsDelete', '_view:reportsWizard', 119, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(193, 'Quality Assurance Dashboard', 'Dashboards', 'Reports', 'Report', 91, 'overview|dashboardReport|dashboards', NULL, NULL, NULL, 'genCSV', 114, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

--
-- 5. updates to table security_functions, delete those new records
--

DELETE FROM `security_functions` 
WHERE `id` > 193 
AND `module` LIKE 'Reports';

