--
-- 1. delete records for old reports
--

DELETE FROM `navigations` 
WHERE `module` LIKE 'Report';

--
-- 2. insert records for new report structrue
-- 

-- need to check existing table to ensure that the biggest id is 153 and the biggest order is 150

INSERT INTO `navigations` (
`id` ,
`module` ,
`plugin` ,
`controller` ,
`header` ,
`title` ,
`action` ,
`pattern` ,
`attributes` ,
`parent` ,
`is_wizard` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES 
('154' , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'General', 'InstitutionGeneral', 'InstitutionGeneral', NULL , '-1', '0', '151', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'Details', 'InstitutionDetails', 'InstitutionDetails', NULL , '154', '0', '152', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'Attendance', 'InstitutionAttendance', 'InstitutionAttendance', NULL , '154', '0', '153', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'Assessment', 'InstitutionAssessment', 'InstitutionAssessment', NULL , '154', '0', '154', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'Behaviors', 'InstitutionBehaviors', 'InstitutionBehaviors', NULL , '154', '0', '155', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'Finance', 'InstitutionFinance', 'InstitutionFinance', NULL , '154', '0', '156', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'Totals', 'InstitutionTotals', 'InstitutionTotals', NULL , '154', '0', '157', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'Quality', 'InstitutionQuality', 'InstitutionQuality', NULL , '154', '0', '158', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STUDENTS', 'General', 'StudentGeneral', 'StudentGeneral', NULL , '154', '0', '159', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STUDENTS', 'Details', 'StudentDetails', 'StudentDetails', NULL , '154', '0', '160', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STUDENTS', 'Finance', 'StudentFinance', 'StudentFinance', NULL , '154', '0', '161', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STUDENTS', 'Health', 'StudentHealth', 'StudentHealth', NULL , '154', '0', '162', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STAFF', 'General', 'StaffGeneral', 'StaffGeneral', NULL , '154', '0', '163', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STAFF', 'Details', 'StaffDetails', 'StaffDetails', NULL , '154', '0', '164', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STAFF', 'Finance', 'StaffFinance', 'StaffFinance', NULL , '154', '0', '165', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STAFF', 'Health', 'StaffHealth', 'StaffHealth', NULL , '154', '0', '166', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STAFF', 'Training', 'StaffTraining', 'StaffTraining', NULL , '154', '0', '167', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'YEARBOOK', 'General', 'YearbookGeneral', 'YearbookGeneral', NULL , '154', '0', '168', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'MAPS', 'General', 'MapGeneral', 'MapGeneral', NULL , '154', '0', '169', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'DASHBOARDS', 'General', 'DashboardGeneral', 'DashboardGeneral', NULL , '154', '0', '170', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'SYSTEM', 'Data Quality', 'SystemDataQuality', 'SystemDataQuality', NULL , '154', '0', '171', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'CUSTOM', 'General', 'CustomGeneral', 'CustomGeneral', NULL , '154', '0', '172', '1', NULL , NULL , '1', '0000-00-00 00:00:00');

--
-- 3. update table reports, change to new category
--

UPDATE `reports` SET 
`category` = 'Institution General Reports', 
`name` = 'Custom Field' 
WHERE `reports`.`id` =13;

UPDATE `reports` SET 
`name` = 'Overview',
`category` = 'Institution General Reports' 
WHERE `reports`.`id` =11;

PDATE `reports` SET 
`name` = 'Programmes',
`category` = 'Institution Details Reports' 
WHERE `reports`.`id` =15;

UPDATE `reports` SET 
`name` = 'Institution Bank Account Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =14;
