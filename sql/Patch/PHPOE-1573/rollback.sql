-- labels
DELETE FROM `labels` WHERE `module` = 'InstitutionRubrics' AND `field` = 'institution_site_section_id';
DELETE FROM `labels` WHERE `module` = 'InstitutionRubrics' AND `field` = 'institution_site_class_id';

-- institution_site_quality_rubric_answers
ALTER TABLE `institution_site_quality_rubric_answers` CHANGE `rubric_criteria_option_id` `rubric_criteria_option_id` INT(11) NOT NULL;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1573';
