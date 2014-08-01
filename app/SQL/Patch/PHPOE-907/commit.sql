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
(NULL , 'Report', NULL, 'Report', 'CUSTOM', 'General', 'index', 'index|^reports', NULL , '154', '0', '172', '1', NULL , NULL , '1', '0000-00-00 00:00:00');

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

UPDATE `reports` SET 
`name` = 'Verification',
`category` = 'Institution Totals Reports' 
WHERE `reports`.`id` =21;

UPDATE `reports` SET 
`name` = 'Student',
`category` = 'Institution Totals Reports' 
WHERE `reports`.`id` =22;

UPDATE `reports` SET 
`name` = 'Training',
`category` = 'Institution Totals Reports' 
WHERE `reports`.`id` =24;

UPDATE `reports` SET 
`name` = 'Staff',
`category` = 'Institution Totals Reports'
 WHERE `reports`.`id` =25;

UPDATE `reports` SET 
`name` = 'Class',
`category` = 'Institution Totals Reports' 
WHERE `reports`.`id` =26;

UPDATE `reports` SET 
`name` = 'Graduate',
`category` = 'Institution Totals Reports' 
WHERE `reports`.`id` =27;

UPDATE `reports` SET 
`name` = 'Attendance',
`category` = 'Institution Totals Reports' 
WHERE `reports`.`id` =28;

UPDATE `reports` SET 
`name` = 'Assessment',
`category` = 'Institution Totals Reports' 
WHERE `reports`.`id` =29;

UPDATE `reports` SET 
`name` = 'Behavior',
`category` = 'Institution Totals Reports' 
WHERE `reports`.`id` =30;

UPDATE `reports` SET 
`name` = 'Textbook',
`category` = 'Institution Totals Reports' 
WHERE `reports`.`id` =31;

UPDATE `reports` SET 
`name` = 'Custom Field',
`category` = 'Institution Totals Reports' 
WHERE `reports`.`id` =32;

UPDATE `reports` SET 
`name` = 'Custom Table',
`category` = 'Institution Totals Reports' 
WHERE `reports`.`id` =33;

UPDATE `reports` SET 
`name` = 'Building',
`category` = 'Institution Totals Reports', 
`module` = 'Institution Totals' 
WHERE `reports`.`id` =51;

UPDATE `reports` SET 
`name` = 'Room',
`category` = 'Institution Totals Reports', 
`module` = 'Institution Totals' 
WHERE `reports`.`id` =52;

UPDATE `reports` SET 
`name` = 'Sanitation',
`category` = 'Institution Totals Reports', 
`module` = 'Institution Totals' 
WHERE `reports`.`id` =53;

UPDATE `reports` SET 
`name` = 'Furniture',
`category` = 'Institution Totals Reports', 
`module` = 'Institution Totals' 
WHERE `reports`.`id` =54;

UPDATE `reports` SET 
`name` = 'Resource',
`category` = 'Institution Totals Reports', 
`module` = 'Institution Totals' 
WHERE `reports`.`id` =55;

UPDATE `reports` SET 
`name` = 'Energy',
`category` = 'Institution Totals Reports', 
`module` = 'Institution Totals' 
WHERE `reports`.`id` =56;

UPDATE `reports` SET 
`name` = 'Water',
`category` = 'Institution Totals Reports', 
`module` = 'Institution Totals' 
WHERE `reports`.`id` =57;

UPDATE `reports` SET 
`name` = 'Income',
`category` = 'Institution Totals Reports', 
`module` = 'Institution Totals' 
WHERE `reports`.`id` =71;

UPDATE `reports` SET 
`name` = 'Expenditure',
`category` = 'Institution Totals Reports', 
`module` = 'Institution Totals' 
WHERE `reports`.`id` =72;

UPDATE `reports` SET 
`name` = 'Overview',
`category` = 'Student General Reports' 
WHERE `reports`.`id` =81;

UPDATE `reports` SET 
`name` = 'Custom Field',
`category` = 'Student General Reports' 
WHERE `reports`.`id` =82;

UPDATE `reports` SET 
`name` = 'Out of School',
`category` = 'Student General Reports' 
WHERE `reports`.`id` =1028

UPDATE `_openemis_`.`reports` SET 
`name` = 'Overview',
`category` = 'Staff General Reports' 
WHERE `reports`.`id` =101;

UPDATE `_openemis_`.`reports` SET 
`name` = 'Custom Field',
`category` = 'Staff General Reports' 
WHERE `reports`.`id` =102;

UPDATE `_openemis_`.`reports` SET 
`name` = 'Courses',
`category` = 'Staff Training Reports' 
WHERE `reports`.`id` =1029;

UPDATE `_openemis_`.`reports` SET 
`name` = 'Completed',
`category` = 'Staff Training Reports' 
WHERE `reports`.`id` =1030;

UPDATE `_openemis_`.`reports` SET 
`name` = 'Needs',
`category` = 'Staff Training Reports' 
WHERE `reports`.`id` =1031;

UPDATE `_openemis_`.`reports` SET 
`name` = 'Uncompleted',
`category` = 'Staff Training Reports' 
WHERE `reports`.`id` =1032;

UPDATE `_openemis_`.`reports` SET 
`name` = 'Trainers',
`category` = 'Staff Training Reports' 
WHERE `reports`.`id` =1033;

UPDATE `_openemis_`.`reports` SET 
`name` = 'Exceptions',
`category` = 'Staff Training Reports' 
WHERE `reports`.`id` =1034;

UPDATE `_openemis_`.`reports` SET 
`name` = 'Statistics',
`category` = 'Staff Training Reports' 
WHERE `reports`.`id` =1035;

UPDATE `_openemis_`.`reports` SET 
`name` = 'Yearbook',
`category` = 'Yearbook General Reports' 
WHERE `reports`.`id` =112;

UPDATE `_openemis_`.`reports` SET 
`name` = 'ECE QA',
`category` = 'Dashboard General Reports' 
WHERE `reports`.`id` =4001;

UPDATE `_openemis_`.`reports` SET 
`name` = 'Non-Responsive Schools',
`category` = 'System Data Quality Reports' 
WHERE `reports`.`id` =151;

UPDATE `_openemis_`.`reports` SET 
`name` = 'Data Discrepancy',
`category` = 'System Data Quality Reports' 
WHERE `reports`.`id` =152;

UPDATE `_openemis_`.`reports` SET 
`category` = 'System Data Quality Reports' 
WHERE `reports`.`id` =153;

UPDATE `_openemis_`.`reports` SET 
`name` = 'Missing Coordinates',
`category` = 'System Data Quality Reports' 
WHERE `reports`.`id` =154;

UPDATE `_openemis_`.`reports` SET 
`name` = 'Institution with No Area',
`category` = 'System Data Quality Reports' 
WHERE `reports`.`id` =1038;

UPDATE `_openemis_`.`reports` SET 
`name` = 'Google Earth',
`category` = 'Map General Reports' 
WHERE `reports`.`id` =111;

--
-- updates to table security_functions
--

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(200, 'Reports Index', 'Reports', 'Reports', 'Report', -1, 'index', NULL, NULL, NULL, NULL, 201, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL , 'General', 'Reports', 'Reports', 'Institutions', '200', 'InstitutionGeneral', NULL , NULL , NULL , '_view:InstitutionGeneralDownload', '202', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Details', 'Reports', 'Reports', 'Institutions', '200', 'InstitutionDetails', NULL , NULL , NULL , '_view:InstitutionDetailsDownload', '203', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Attendance', 'Reports', 'Reports', 'Institutions', '200', 'InstitutionAttendance', NULL , NULL , NULL , '_view:InstitutionAttendanceDownload', '204', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Assessment', 'Reports', 'Reports', 'Institutions', '200', 'InstitutionAssessment', NULL , NULL , NULL , '_view:InstitutionAssessmentDownload', '205', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Behaviors', 'Reports', 'Reports', 'Institutions', '200', 'InstitutionBehaviors', NULL , NULL , NULL , '_view:InstitutionBehaviorsDownload', '206', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Finance', 'Reports', 'Reports', 'Institutions', '200', 'InstitutionFinance', NULL , NULL , NULL , '_view:InstitutionFinanceDownload', '207', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Totals', 'Reports', 'Reports', 'Institutions', '200', 'InstitutionTotals', NULL , NULL , NULL , '_view:InstitutionTotalsDownload', '208', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Quality', 'Reports', 'Reports', 'Institutions', '200', 'InstitutionQuality', NULL , NULL , NULL , '_view:InstitutionQualityDownload', '209', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),

(NULL , 'General', 'Reports', 'Reports', 'Students', '200', 'StudentGeneral', NULL , NULL , NULL , '_view:StudentGeneralDownload', '210', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Details', 'Reports', 'Reports', 'Students', '200', 'StudentDetails', NULL , NULL , NULL , '_view:StudentDetailsDownload', '211', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Finance', 'Reports', 'Reports', 'Students', '200', 'StudentFinance', NULL , NULL , NULL , '_view:StudentFinanceDownload', '212', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Health', 'Reports', 'Reports', 'Students', '200', 'StudentHealth', NULL , NULL , NULL , '_view:StudentHealthDownload', '213', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),

(NULL , 'General', 'Reports', 'Reports', 'Staff', '200', 'StaffGeneral', NULL , NULL , NULL , '_view:StaffGeneralDownload', '214', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Details', 'Reports', 'Reports', 'Staff', '200', 'StaffDetails', NULL , NULL , NULL , '_view:StaffDetailsDownload', '215', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Finance', 'Reports', 'Reports', 'Staff', '200', 'StaffFinance', NULL , NULL , NULL , '_view:StaffFinanceDownload', '216', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Health', 'Reports', 'Reports', 'Staff', '200', 'StaffHealth', NULL , NULL , NULL , '_view:StaffHealthDownload', '217', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Training', 'Reports', 'Reports', 'Staff', '200', 'StaffTraining', NULL , NULL , NULL , '_view:StaffTrainingDownload', '218', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),

(NULL , 'General', 'Reports', 'Reports', 'Yearbook', '200', 'YearbookGeneral', NULL , NULL , NULL , '_view:YearbookGeneralDownload', '219', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),

(NULL , 'General', 'Reports', 'Reports', 'Maps', '200', 'MapGeneral', NULL , NULL , NULL , '_view:MapGeneralDownload', '220', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),

(NULL , 'General', 'Reports', 'Reports', 'Dashboards', '200', 'DashboardGeneral', NULL , NULL , NULL , '_view:DashboardGeneralDownload', '221', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),

(NULL , 'Data Quality', 'Reports', 'Reports', 'System', '200', 'SystemDataQuality', NULL , NULL , NULL , '_view:SystemDataQualityDownload', '222', '1', NULL , NULL , '1', '0000-00-00 00:00:00'),

(NULL , 'General', 'Report', 'Reports', 'Custom', '200', 'index|reportsView', NULL, '_view:reportsNew|reportsWizard', '_view:reportsDelete' , '_view:reportsWizard', '223', '1', NULL , NULL , '1', '0000-00-00 00:00:00');

