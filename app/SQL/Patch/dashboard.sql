ALTER TABLE `institution_sites` ADD `institution_site_area_id` INT( 11 ) NULL AFTER `institution_id` ;

--
-- Move the Institution > Report > Dashboard to the 1st item. Before the “General”.
--

INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Report', 'Dashboards', 'Dashboards', 'REPORTS', 'Dashboards', 'dashboardReport', 'dashboardReport', NULL, 123, 0, 141, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'Institution', 'Dashboards', 'Dashboards', 'REPORTS', 'Dashboards', 'general', 'general', NULL, 3, 0, 34, 1, NULL, NULL, 1, '0000-00-00 00:00:00');


INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(190, 'Quality Assurance Dashboard', 'Dashboards', 'Institution Site Reports', 8, 'InstitutionQA|general', NULL, NULL, NULL, NULL, 190, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(191, 'Quality Assurance Dashboard', 'Dashboards', 'Reports', 91, 'overview|dashboardReport|dashboards', NULL, NULL, NULL, 'genCSV', 191, 1, NULL, NULL, 1, '0000-00-00 00:00:00');
