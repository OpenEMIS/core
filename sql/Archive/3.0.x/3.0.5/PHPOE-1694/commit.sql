UPDATE `labels` SET `en` = 'Class Name' WHERE `labels`.`module` = 'InstitutionSiteSections' AND `labels`.`field` = 'name';
UPDATE `labels` SET `en` = 'Name' WHERE `labels`.`module` = 'InstitutionSiteClasses' AND `labels`.`field` = 'name';
UPDATE `labels` SET `en` = 'Subject' WHERE `labels`.`module` = 'Absences' AND `labels`.`field` = 'institution_site_class_id';
UPDATE `labels` SET `en` = 'Class' WHERE `labels`.`module` = 'Absences' AND `labels`.`field` = 'institution_site_section_id';
UPDATE `labels` SET `en` = 'Select Class' WHERE `labels`.`module` = 'Absences' AND `labels`.`field` = 'select_section';

-- Fix translations
UPDATE `labels` SET `en` = 'العربية' WHERE `labels`.`module` = 'Translations' AND `labels`.`field` = 'ar';
UPDATE `labels` SET `en` = 'español' WHERE `labels`.`module` = 'Translations' AND `labels`.`field` = 'es';
UPDATE `labels` SET `en` = 'Français' WHERE `labels`.`module` = 'Translations' AND `labels`.`field` = 'fr';
UPDATE `labels` SET `en` = 'русский' WHERE `labels`.`module` = 'Translations' AND `labels`.`field` = 'ru';
UPDATE `labels` SET `en` = '中文' WHERE `labels`.`module` = 'Translations' AND `labels`.`field` = 'zh';

INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('InstitutionSiteSections', 'classes', NULL, 'Subjects', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('InstitutionSiteSections', 'number_of_sections', NULL, 'Number Of Classes', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('Students', 'section', NULL, 'Class', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StudentSections', 'institution_site_section_id', NULL, 'Class', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StudentClasses', 'institution_site_section_id', NULL, 'Class', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StudentClasses', 'institution_site_class_id', NULL, 'Name', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StaffClasses', 'institution_site_section', NULL, 'Class', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StaffClasses', 'institution_site_class_id', NULL, 'Name', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('StudentBehaviours', 'section', NULL, 'Class', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `created_user_id`, `created`) VALUES ('InstitutionSiteStudentAbsences', 'section', NULL, 'Class', '1', NOW());

UPDATE `security_functions` SET `name` = 'Classes' WHERE `id` IN (1006, 2012, 3013);
UPDATE `security_functions` SET `name` = 'Subjects' WHERE `id` IN (1007, 2013, 3014);

