-- Drop unused columns and not show in reports
ALTER TABLE `student_behaviours` DROP `student_action_category_id` ;
ALTER TABLE `staff_behaviours` DROP `staff_action_category_id` ;

-- Move Institution -> Reports -> Dashboard to Institution -> Quality -> Dashboard
UPDATE `navigations` SET `header` = 'Quality' WHERE `module` = 'Institution' AND `controller` = 'Dashboards' AND `header` = 'Reports';

-- Remove all links under Institution -> Reports, Staff -> Reports
DELETE FROM `navigations` WHERE `module` = 'Institution' AND `header` = 'Reports';
DELETE FROM `navigations` WHERE `controller` = 'Staff' AND `header` = 'Reports';
DELETE FROM `security_functions` WHERE `category` = 'Reports' AND `controller` <> 'Dashboards';

-- Update security_functions to enable execute permissions to be configurable
UPDATE `security_functions` SET `_execute` = '_view:excel' WHERE `name` = 'Institution' AND `controller` = 'InstitutionSites' AND `category` = 'General';
UPDATE `security_functions` SET `_execute` = '_view:qualityVisitExcel' WHERE `name` = 'Visits' AND `controller` = 'Quality' AND `module` = 'Institutions';
UPDATE `security_functions` SET `_execute` = '_view:StudentBehaviour.excel' WHERE `name` = 'Students' AND `controller` = 'InstitutionSites' AND `category` = 'Behaviour';
UPDATE `security_functions` SET `_execute` = '_view:StaffBehaviour.excel' WHERE `name` = 'Staff' AND `controller` = 'InstitutionSites' AND `category` = 'Behaviour';
UPDATE `security_functions` SET `category` = 'Quality' WHERE `controller` = 'Dashboards' AND `name` = 'Dashboards' AND `module` = 'Institutions';

UPDATE `navigations` SET `action` = 'Absence', `pattern` = 'Absence' WHERE `plugin` = 'Students' AND `controller` = 'Students' AND `title` = 'Absence';
