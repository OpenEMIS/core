UPDATE `security_functions` SET `_view` = 'behaviourStudentList|behaviourStudent|behaviourStudentView',
`_edit` = '_view:behaviourStudentEdit',
`_add` = '_view:behaviourStudentAdd',
`_delete` = '_view:behaviourStudentDelete' WHERE `security_functions`.`id` =21;

UPDATE `security_functions` SET `_view` = 'behaviourStaffList|behaviourStaff|behaviourStaffView',
`_edit` = '_view:behaviourStaffEdit',
`_add` = '_view:behaviourStaffAdd',
`_delete` = '_view:behaviourStaffDelete' WHERE `security_functions`.`id` =103;

INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(143, 'Institution', NULL, 'InstitutionSites', 'BEHAVIOURS', 'Students', 'behaviourStudentList', 'behaviourStudent', NULL, 3, 0, 35, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(144, 'Institution', NULL, 'InstitutionSites', 'BEHAVIOURS', 'Staff', 'behaviourStaffList', 'behaviourStaff', NULL, 3, 0, 36, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

--
-- for Assessments 
--
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
VALUES (
NULL , 'Institution', NULL , 'InstitutionSites', 'ASSESSMENTS', 'Results', 'assessments', 'assessments', NULL , '3', '0', '146', '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);
