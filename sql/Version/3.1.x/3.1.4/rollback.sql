-- labels
DELETE FROM `labels` WHERE `module` = 'InstitutionRubrics' AND `field` = 'institution_site_section_id';
DELETE FROM `labels` WHERE `module` = 'InstitutionRubrics' AND `field` = 'institution_site_class_id';

-- institution_site_quality_rubrics
ALTER TABLE `institution_site_quality_rubrics` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Draft, 2 -> Completed';

-- institution_site_quality_rubric_answers
ALTER TABLE `institution_site_quality_rubric_answers` CHANGE `rubric_criteria_option_id` `rubric_criteria_option_id` INT(11) NOT NULL;

-- security_functions
UPDATE `security_functions` SET `name` = 'Rubrics', `category` = 'Quality', `_view` = 'Rubrics.index|Rubrics.view', `_edit` = 'Rubrics.edit', `_add` = NULL, `_delete` = 'Rubrics.remove', `_execute` = NULL WHERE `id` = 1026;
DELETE FROM `security_functions` WHERE `id` = 1029;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1573';

UPDATE `config_items` SET `value` = '3.1.3' WHERE `code` = 'db_version';
