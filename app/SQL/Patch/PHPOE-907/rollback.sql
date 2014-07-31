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
WHERE `pattern` = '154';

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
