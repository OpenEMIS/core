DELETE FROM `labels` WHERE `module` = 'InstitutionSiteSections' AND `field` = 'classes';
DELETE FROM `labels` WHERE `module` = 'InstitutionSiteSections' AND `field` = 'number_of_sections';
DELETE FROM `labels` WHERE `module` = 'StudentSections' AND `field` = 'institution_site_section_id';
DELETE FROM `labels` WHERE `module` = 'StudentClasses' AND `field` = 'institution_site_section_id';
DELETE FROM `labels` WHERE `module` = 'StudentClasses' AND `field` = 'institution_site_class_id';
DELETE FROM `labels` WHERE `module` = 'StaffClasses' AND `field` = 'institution_site_section';
DELETE FROM `labels` WHERE `module` = 'StaffClasses' AND `field` = 'institution_site_class_id';
