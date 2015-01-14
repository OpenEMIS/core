ALTER TABLE `student_behaviours` ADD `student_action_category_id` INT( 11 ) NOT NULL DEFAULT '0' AFTER `student_behaviour_category_id` ;
ALTER TABLE `staff_behaviours` ADD `staff_action_category_id` INT( 11 ) NOT NULL DEFAULT '0' AFTER `staff_behaviour_category_id` ;

UPDATE `navigations` SET `header` = 'Reports' WHERE `module` = 'Institution' AND `controller` = 'Dashboards' AND `header` = 'Quality';

INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Institution', NULL, 'InstitutionReports', 'Reports', 'General', 'general', 'general', NULL, 3, 0, 44, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Institution', NULL, 'InstitutionReports', 'Reports', 'Details', 'details', 'details', NULL, 3, 0, 45, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Institution', NULL, 'InstitutionReports', 'Reports', 'Totals', 'totals', 'totals', NULL, 3, 0, 47, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Institution', NULL, 'InstitutionReports', 'Reports', 'Quality', 'quality', 'quality', NULL, 3, 0, 48, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Institution', NULL, 'InstitutionReports', 'Reports', 'Finance', 'finance', 'finance', NULL, 3, 0, 46, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Staff', 'Staff', 'Staff', 'Reports', 'Quality', 'report', 'report|reportGen', NULL, 89, 0, 147, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'General', 'InstitutionReports', 'Institutions', 'Reports', 8, 'general', NULL, NULL, NULL, 'generate', 41, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Details', 'InstitutionReports', 'Institutions', 'Reports', 8, 'details', NULL, NULL, NULL, 'generate', 38, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Quality', 'InstitutionReports', 'Institutions', 'Reports', 8, 'quality', NULL, NULL, NULL, 'generate', 42, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Totals', 'InstitutionReports', 'Institutions', 'Reports', 8, 'totals', NULL, NULL, NULL, 'generate', 43, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Finance', 'InstitutionReports', 'Institutions', 'Reports', 8, 'finance', NULL, NULL, NULL, 'generate', 39, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Quality', 'Staff', 'Staff', 'Reports', 84, 'report', NULL, NULL, NULL, 'reportGen', 108, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

UPDATE `security_functions` SET `_execute` = NULL WHERE `name` = 'Institution' AND `controller` = 'InstitutionSites' AND `category` = 'General';
UPDATE `security_functions` SET `_execute` = NULL WHERE `name` = 'Visits' AND `controller` = 'Quality' AND `module` = 'Institutions';
UPDATE `security_functions` SET `_execute` = NULL WHERE `name` = 'Students' AND `controller` = 'InstitutionSites' AND `category` = 'Behaviour';
UPDATE `security_functions` SET `_execute` = NULL WHERE `name` = 'Staff' AND `controller` = 'InstitutionSites' AND `category` = 'Behaviour';

UPDATE `navigations` SET `action` = 'absence', `pattern` = 'absence' WHERE `plugin` = 'Students' AND `controller` = 'Students' AND `title` = 'Absence';
