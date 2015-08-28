UPDATE field_options SET plugin = NULL, code = 'InstitutionSiteGenders' WHERE plugin = 'Institution' and code = 'Genders';
UPDATE field_options SET plugin = NULL, code = 'InstitutionSiteLocalities' WHERE plugin = 'Institution' and code = 'Localities';
UPDATE field_options SET plugin = NULL, code = 'InstitutionSiteOwnerships' WHERE plugin = 'Institution' and code = 'Ownerships';
UPDATE field_options SET plugin = NULL, code = 'InstitutionSiteSectors' WHERE plugin = 'Institution' and code = 'Sectors';
UPDATE field_options SET plugin = NULL, code = 'InstitutionSiteStatuses' WHERE plugin = 'Institution' and code = 'Statuses';
UPDATE field_options SET plugin = NULL, code = 'InstitutionSiteTypes' WHERE plugin = 'Institution' and code = 'Types';

UPDATE field_options SET plugin = 'Student' WHERE code = 'StudentAbsenceReasons';
UPDATE field_options SET plugin = 'Student' WHERE code = 'StudentBehaviourCategories';
UPDATE field_options SET plugin = 'Staff' WHERE code = 'StaffAbsenceReasons';
UPDATE field_options SET plugin = 'Staff' WHERE code = 'StaffBehaviourCategories';



INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Students', 'StudentCategories', 'Categories', 'Student', NULL, 14, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
('Students', 'Genders', 'Gender', 'Student', NULL, 15, 0, NULL, NULL, 1, '2014-08-12 17:32:25'),
('Students', 'StudentStatuses', 'Status', 'Student', NULL, 16, 0, NULL, NULL, 1, '0000-00-00 00:00:00');

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1904';
