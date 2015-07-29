UPDATE `labels` SET `en` = 'Class Name' WHERE `labels`.`module` = 'InstitutionSiteSections' AND `labels`.`field` = 'name';
UPDATE `labels` SET `en` = 'Name' WHERE `labels`.`module` = 'InstitutionSiteClasses' AND `labels`.`field` = 'name';

INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('InstitutionSiteSections', 'classes', NULL, 'Subjects', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('InstitutionSiteSections', 'number_of_sections', NULL, 'Number Of Classes', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StudentSections', 'institution_site_section_id', NULL, 'Class', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StudentClasses', 'institution_site_section_id', NULL, 'Class', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StudentClasses', 'institution_site_class_id', NULL, 'Name', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StaffClasses', 'institution_site_section', NULL, 'Class', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StaffClasses', 'institution_site_class_id', NULL, 'Name', '1', NOW());

UPDATE `security_functions` SET `name` = 'Classes' WHERE `id` IN (1006, 2012, 3013);
UPDATE `security_functions` SET `name` = 'Subjects' WHERE `id` IN (1007, 2013, 3014);

