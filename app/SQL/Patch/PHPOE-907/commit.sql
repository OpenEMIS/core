--
-- 1. delete records for old reports
--

DELETE FROM `navigations` 
WHERE `module` LIKE 'Report' 
AND `id` <= 147;

--
-- 2. insert records for new report structrue
-- 
SET @reportLatestOrder := 0;
SELECT MAX(`order`) INTO @reportLatestOrder from `navigations`;

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
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'General', 'InstitutionGeneral', 'InstitutionGeneral', NULL , '-1', '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00');

SET @reportLatestId := 0;

SELECT `id` INTO @reportLatestId FROM `navigations` WHERE `controller` = 'Reports' AND `action` = 'InstitutionGeneral';

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
) VALUES 
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'Details', 'InstitutionDetails', 'InstitutionDetails', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'Attendance', 'InstitutionAttendance', 'InstitutionAttendance', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'Assessment', 'InstitutionAssessment', 'InstitutionAssessment', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'Behaviors', 'InstitutionBehaviors', 'InstitutionBehaviors', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'Finance', 'InstitutionFinance', 'InstitutionFinance', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'Totals', 'InstitutionTotals', 'InstitutionTotals', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'INSTITUTIONS', 'Quality', 'InstitutionQuality', 'InstitutionQuality', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STUDENTS', 'General', 'StudentGeneral', 'StudentGeneral', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STUDENTS', 'Details', 'StudentDetails', 'StudentDetails', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STUDENTS', 'Finance', 'StudentFinance', 'StudentFinance', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STUDENTS', 'Health', 'StudentHealth', 'StudentHealth', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STAFF', 'General', 'StaffGeneral', 'StaffGeneral', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STAFF', 'Details', 'StaffDetails', 'StaffDetails', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STAFF', 'Finance', 'StaffFinance', 'StaffFinance', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STAFF', 'Health', 'StaffHealth', 'StaffHealth', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'STAFF', 'Training', 'StaffTraining', 'StaffTraining', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'YEARBOOK', 'General', 'YearbookGeneral', 'YearbookGeneral', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'MAPS', 'General', 'MapGeneral', 'MapGeneral', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'DASHBOARDS', 'General', 'DashboardGeneral', 'DashboardGeneral', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', 'Reports', 'Reports', 'SYSTEM', 'Data Quality', 'SystemDataQuality', 'SystemDataQuality', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Report', NULL, 'Report', 'CUSTOM', 'General', 'index', 'index|^reports', NULL , @reportLatestId, '0', (@reportLatestOrder := @reportLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00');

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

UPDATE `reports` SET 
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
WHERE `reports`.`id` =1028;

UPDATE `reports` SET 
`name` = 'Overview',
`category` = 'Staff General Reports' 
WHERE `reports`.`id` =101;

UPDATE `reports` SET 
`name` = 'Custom Field',
`category` = 'Staff General Reports' 
WHERE `reports`.`id` =102;

UPDATE `reports` SET 
`name` = 'Courses',
`category` = 'Staff Training Reports' 
WHERE `reports`.`id` =1029;

UPDATE `reports` SET 
`name` = 'Completed',
`category` = 'Staff Training Reports' 
WHERE `reports`.`id` =1030;

UPDATE `reports` SET 
`name` = 'Needs',
`category` = 'Staff Training Reports' 
WHERE `reports`.`id` =1031;

UPDATE `reports` SET 
`name` = 'Uncompleted',
`category` = 'Staff Training Reports' 
WHERE `reports`.`id` =1032;

UPDATE `reports` SET 
`name` = 'Trainers',
`category` = 'Staff Training Reports' 
WHERE `reports`.`id` =1033;

UPDATE `reports` SET 
`name` = 'Exceptions',
`category` = 'Staff Training Reports' 
WHERE `reports`.`id` =1034;

UPDATE `reports` SET 
`name` = 'Statistics',
`category` = 'Staff Training Reports' 
WHERE `reports`.`id` =1035;

UPDATE `reports` SET 
`name` = 'Yearbook',
`category` = 'Yearbook General Reports' 
WHERE `reports`.`id` =112;

UPDATE `reports` SET 
`name` = 'ECE QA',
`category` = 'Dashboard General Reports' 
WHERE `reports`.`id` =4001;

UPDATE `reports` SET 
`name` = 'Non-Responsive Schools',
`category` = 'System Data Quality Reports' 
WHERE `reports`.`id` =151;

UPDATE `reports` SET 
`name` = 'Data Discrepancy',
`category` = 'System Data Quality Reports' 
WHERE `reports`.`id` =152;

UPDATE `reports` SET 
`category` = 'System Data Quality Reports' 
WHERE `reports`.`id` =153;

UPDATE `reports` SET 
`name` = 'Missing Coordinates',
`category` = 'System Data Quality Reports' 
WHERE `reports`.`id` =154;

UPDATE `reports` SET 
`name` = 'Institution with No Area',
`category` = 'System Data Quality Reports' 
WHERE `reports`.`id` =1038;

UPDATE `reports` SET 
`name` = 'Google Earth',
`category` = 'Map General Reports' 
WHERE `reports`.`id` =111;


--
-- 4. delete existing security_functions records
--

DELETE FROM `security_functions`
WHERE `id` <= 193 
AND `module` LIKE 'Reports';

--
-- 5. updates to table security_functions, add new records
--

SET @securityFuncLatestOrder := 0;

SELECT MAX(`order`) INTO @securityFuncLatestOrder from `security_functions`;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL , 'General', 'Reports', 'Reports', 'Institutions', '-1', 'InstitutionGeneral', NULL , NULL , NULL , '_view:InstitutionGeneralDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Details', 'Reports', 'Reports', 'Institutions', '-1', 'InstitutionDetails', NULL , NULL , NULL , '_view:InstitutionDetailsDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Attendance', 'Reports', 'Reports', 'Institutions', '-1', 'InstitutionAttendance', NULL , NULL , NULL , '_view:InstitutionAttendanceDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Assessment', 'Reports', 'Reports', 'Institutions', '-1', 'InstitutionAssessment', NULL , NULL , NULL , '_view:InstitutionAssessmentDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Behaviors', 'Reports', 'Reports', 'Institutions', '-1', 'InstitutionBehaviors', NULL , NULL , NULL , '_view:InstitutionBehaviorsDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Finance', 'Reports', 'Reports', 'Institutions', '-1', 'InstitutionFinance', NULL , NULL , NULL , '_view:InstitutionFinanceDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Totals', 'Reports', 'Reports', 'Institutions', '-1', 'InstitutionTotals', NULL , NULL , NULL , '_view:InstitutionTotalsDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Quality', 'Reports', 'Reports', 'Institutions', '-1', 'InstitutionQuality', NULL , NULL , NULL , '_view:InstitutionQualityDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),

(NULL , 'General', 'Reports', 'Reports', 'Students', '-1', 'StudentGeneral', NULL , NULL , NULL , '_view:StudentGeneralDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Details', 'Reports', 'Reports', 'Students', '-1', 'StudentDetails', NULL , NULL , NULL , '_view:StudentDetailsDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Finance', 'Reports', 'Reports', 'Students', '-1', 'StudentFinance', NULL , NULL , NULL , '_view:StudentFinanceDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Health', 'Reports', 'Reports', 'Students', '-1', 'StudentHealth', NULL , NULL , NULL , '_view:StudentHealthDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),

(NULL , 'General', 'Reports', 'Reports', 'Staff', '-1', 'StaffGeneral', NULL , NULL , NULL , '_view:StaffGeneralDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Details', 'Reports', 'Reports', 'Staff', '-1', 'StaffDetails', NULL , NULL , NULL , '_view:StaffDetailsDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Finance', 'Reports', 'Reports', 'Staff', '-1', 'StaffFinance', NULL , NULL , NULL , '_view:StaffFinanceDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Health', 'Reports', 'Reports', 'Staff', '-1', 'StaffHealth', NULL , NULL , NULL , '_view:StaffHealthDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),
(NULL , 'Training', 'Reports', 'Reports', 'Staff', '-1', 'StaffTraining', NULL , NULL , NULL , '_view:StaffTrainingDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),

(NULL , 'General', 'Reports', 'Reports', 'Yearbook', '-1', 'YearbookGeneral', NULL , NULL , NULL , '_view:YearbookGeneralDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),

(NULL , 'General', 'Reports', 'Reports', 'Maps', '-1', 'MapGeneral', NULL , NULL , NULL , '_view:MapGeneralDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),

(NULL , 'General', 'Reports', 'Reports', 'Dashboards', '-1', 'DashboardGeneral', NULL , NULL , NULL , '_view:DashboardGeneralDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),

(NULL , 'Data Quality', 'Reports', 'Reports', 'System', '-1', 'SystemDataQuality', NULL , NULL , NULL , '_view:SystemDataQualityDownload', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00'),

(NULL , 'General', 'Report', 'Reports', 'Custom', '-1', 'index|reportsView', NULL, '_view:reportsNew|reportsWizard', '_view:reportsDelete' , '_view:reportsWizard', (@securityFuncLatestOrder := @securityFuncLatestOrder + 1), '1', NULL , NULL , '1', '0000-00-00 00:00:00');

