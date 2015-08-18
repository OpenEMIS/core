-- labels
DELETE FROM `labels` WHERE `module` = 'InstitutionRubrics' AND `field` = 'institution_site_section_id';
DELETE FROM `labels` WHERE `module` = 'InstitutionRubrics' AND `field` = 'institution_site_class_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1573';
